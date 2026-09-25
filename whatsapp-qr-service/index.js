require('dotenv').config();

const fs = require('fs');
const path = require('path');
const express = require('express');
const pino = require('pino');
const QRCode = require('qrcode');

const {
  default: makeWASocket,
  useMultiFileAuthState,
  DisconnectReason,
  fetchLatestBaileysVersion,
} = require('@whiskeysockets/baileys');

const PORT = process.env.PORT || 3200;
const SHARED_SECRET = process.env.SHARED_SECRET || process.env.WHATSAPP_QR_SHARED_SECRET || '';
const LARAVEL_WEBHOOK_URL = process.env.LARAVEL_WEBHOOK_URL || '';
const SESSIONS_DIR = path.join(__dirname, 'sessions');

if (!fs.existsSync(SESSIONS_DIR)) {
  fs.mkdirSync(SESSIONS_DIR, { recursive: true });
}

const logger = pino({ level: 'silent' });

// In-memory session registry: sessionId -> { sock, status, latestQr }
const sessions = new Map();

function sessionDir(sessionId) {
  return path.join(SESSIONS_DIR, sessionId);
}

function getOrInitEntry(sessionId) {
  let entry = sessions.get(sessionId);
  if (!entry) {
    entry = { sock: null, status: 'disconnected', latestQr: null, connecting: false };
    sessions.set(sessionId, entry);
  }
  return entry;
}

async function postWebhook(payload) {
  if (!LARAVEL_WEBHOOK_URL) return;
  try {
    const headers = { 'Content-Type': 'application/json' };
    if (SHARED_SECRET) headers.Authorization = `Bearer ${SHARED_SECRET}`;
    await fetch(LARAVEL_WEBHOOK_URL, {
      method: 'POST',
      headers,
      body: JSON.stringify(payload),
    });
  } catch (err) {
    console.error(`[webhook] failed to POST for session ${payload.session_id}:`, err.message);
  }
}

async function clearAuthFolder(sessionId) {
  try {
    await fs.promises.rm(sessionDir(sessionId), { recursive: true, force: true });
  } catch (err) {
    console.error(`[session ${sessionId}] failed to clear auth folder:`, err.message);
  }
}

async function startSession(sessionId) {
  const entry = getOrInitEntry(sessionId);

  // Idempotent: already connected or already have a pending QR / in-flight connect attempt.
  if (entry.status === 'connected' || entry.status === 'qr_pending' || entry.connecting) {
    return entry;
  }

  entry.connecting = true;

  try {
    const { state, saveCreds } = await useMultiFileAuthState(sessionDir(sessionId));
    let version;
    try {
      ({ version } = await fetchLatestBaileysVersion());
    } catch (err) {
      version = undefined; // fall back to Baileys' bundled default version
    }

    const sock = makeWASocket({
      version,
      auth: state,
      logger,
      printQRInTerminal: false,
    });

    entry.sock = sock;

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
      const { connection, lastDisconnect, qr } = update;

      if (qr) {
        try {
          const pngBuffer = await QRCode.toBuffer(qr, { type: 'png' });
          entry.latestQr = pngBuffer.toString('base64');
          entry.status = 'qr_pending';
          await postWebhook({ session_id: sessionId, event: 'status', status: 'qr_pending' });
        } catch (err) {
          console.error(`[session ${sessionId}] failed to render QR:`, err.message);
        }
      }

      if (connection === 'open') {
        entry.status = 'connected';
        entry.latestQr = null;
        entry.connecting = false;
        await postWebhook({ session_id: sessionId, event: 'status', status: 'connected' });
      }

      if (connection === 'close') {
        entry.connecting = false;
        const statusCode = lastDisconnect?.error?.output?.statusCode;
        const loggedOut = statusCode === DisconnectReason.loggedOut;

        entry.status = 'disconnected';
        entry.latestQr = null;
        await postWebhook({ session_id: sessionId, event: 'status', status: 'disconnected' });

        if (loggedOut) {
          await clearAuthFolder(sessionId);
          sessions.delete(sessionId);
        } else {
          // Standard Baileys reconnect idiom: retry unless it was a real logout.
          setTimeout(() => {
            startSession(sessionId).catch((err) => {
              console.error(`[session ${sessionId}] reconnect attempt failed:`, err.message);
            });
          }, 2000);
        }
      }
    });

    sock.ev.on('messages.upsert', async ({ messages, type }) => {
      if (type !== 'notify') return;
      for (const m of messages) {
        try {
          if (!m.message || m.key.fromMe) continue;
          const remoteJid = m.key.remoteJid || '';
          if (!remoteJid.endsWith('@s.whatsapp.net')) continue; // skip groups/broadcasts

          const body =
            m.message.conversation ||
            m.message.extendedTextMessage?.text ||
            m.message.imageMessage?.caption ||
            m.message.videoMessage?.caption ||
            null;

          if (!body) continue; // only plain text bodies are relayed for now

          const from = remoteJid.replace('@s.whatsapp.net', '');
          const timestamp = typeof m.messageTimestamp === 'number'
            ? m.messageTimestamp
            : Number(m.messageTimestamp) || Math.floor(Date.now() / 1000);

          await postWebhook({
            session_id: sessionId,
            event: 'message',
            from,
            body,
            external_message_id: m.key.id,
            timestamp,
          });
        } catch (err) {
          console.error(`[session ${sessionId}] failed to relay incoming message:`, err.message);
        }
      }
    });

    return entry;
  } catch (err) {
    entry.connecting = false;
    entry.status = 'disconnected';
    throw err;
  }
}

async function sendMessage(sessionId, to, text) {
  const entry = sessions.get(sessionId);
  if (!entry || entry.status !== 'connected' || !entry.sock) {
    return { success: false, error: 'Session is not connected.' };
  }
  try {
    const jid = `${to}@s.whatsapp.net`;
    const result = await entry.sock.sendMessage(jid, { text });
    return { success: true, id: result?.key?.id || null };
  } catch (err) {
    return { success: false, error: err.message || 'Failed to send message.' };
  }
}

async function logoutSession(sessionId) {
  const entry = sessions.get(sessionId);
  if (entry?.sock) {
    try {
      await entry.sock.logout();
    } catch (err) {
      // Ignore — we clear the auth state regardless below.
    }
  }
  await clearAuthFolder(sessionId);
  sessions.delete(sessionId);
}

function publicState(sessionId) {
  const entry = sessions.get(sessionId);
  if (!entry) return { status: 'disconnected', qr: null };
  return {
    status: entry.status,
    qr: entry.status === 'qr_pending' ? entry.latestQr : null,
  };
}

// ---------------------------------------------------------------------------
// HTTP API
// ---------------------------------------------------------------------------

const app = express();
app.use(express.json());

app.use((req, res, next) => {
  if (!SHARED_SECRET) return next(); // dev-convenience fallback: no secret configured, skip check

  const header = req.headers.authorization || '';
  const token = header.startsWith('Bearer ') ? header.slice(7) : null;

  if (token !== SHARED_SECRET) {
    return res.status(401).json({ error: 'Unauthorized' });
  }
  next();
});

app.post('/sessions/:sessionId/start', async (req, res) => {
  const { sessionId } = req.params;
  try {
    await startSession(sessionId);
    res.json(publicState(sessionId));
  } catch (err) {
    console.error(`[session ${sessionId}] start failed:`, err.message);
    res.status(500).json({ error: 'Failed to start session.' });
  }
});

app.get('/sessions/:sessionId/status', (req, res) => {
  res.json(publicState(req.params.sessionId));
});

app.post('/sessions/:sessionId/send', async (req, res) => {
  const { sessionId } = req.params;
  const { to, text } = req.body || {};

  if (!to || !text) {
    return res.status(400).json({ success: false, error: '"to" and "text" are required.' });
  }

  const result = await sendMessage(sessionId, to, text);
  res.json(result);
});

app.post('/sessions/:sessionId/logout', async (req, res) => {
  const { sessionId } = req.params;
  try {
    await logoutSession(sessionId);
    res.json({ status: 'disconnected', qr: null });
  } catch (err) {
    console.error(`[session ${sessionId}] logout failed:`, err.message);
    res.status(500).json({ error: 'Failed to logout session.' });
  }
});

app.listen(PORT, () => {
  console.log(`whatsapp-qr-service listening on port ${PORT}`);

  // Kick off reconnect attempts for any persisted sessions, without blocking startup.
  fs.promises
    .readdir(SESSIONS_DIR, { withFileTypes: true })
    .then((entries) => {
      const ids = entries.filter((e) => e.isDirectory()).map((e) => e.name);
      for (const id of ids) {
        startSession(id).catch((err) => {
          console.error(`[session ${id}] auto-reconnect failed:`, err.message);
        });
      }
    })
    .catch((err) => {
      console.error('Failed to scan sessions directory for auto-reconnect:', err.message);
    });
});
