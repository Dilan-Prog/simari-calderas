# whatsapp-qr-service

Standalone Node.js microservice that connects to WhatsApp via the unofficial
"WhatsApp Web" protocol using [`@whiskeysockets/baileys`](https://github.com/WhiskeySockets/Baileys)
(pure JS, no headless browser). It lets a user link a WhatsApp number by
scanning a QR code — the same flow as linking WhatsApp Web in a browser —
and supports **multiple independent sessions** at once, each identified by a
`session_id` chosen by the calling app.

It exposes a small HTTP API so another application (in this repo's case, the
Laravel CRM) can start sessions, check status, and send messages, and it
pushes incoming messages / connection-status changes to a webhook on that
app. This service **never touches any database** — all persistence happens
on the Laravel side via the webhook.

This folder is completely independent from the Laravel app: its own
`package.json`, own `node_modules`, own process. It is not part of the
Laravel build/deploy.

## Running locally

```bash
cd whatsapp-qr-service
npm install
cp .env.example .env
# edit .env: set PORT, SHARED_SECRET, LARAVEL_WEBHOOK_URL
npm start
```

Baileys auth state for each session is persisted under `./sessions/<sessionId>/`
(gitignored — these folders contain real credentials, never commit them).
On process boot, this service scans `./sessions/` and attempts to reconnect
any previously-started sessions in the background, without blocking server
startup.

## Auth

Every request to this service must include:

```
Authorization: Bearer <SHARED_SECRET>
```

`SHARED_SECRET` is read from the env var `SHARED_SECRET` (alias
`WHATSAPP_QR_SHARED_SECRET` also works). If this service has **no** secret
configured (empty/unset), the auth check is skipped entirely — a
dev-convenience fallback. Always set a real secret outside local dev.

## HTTP API (Laravel → this service)

### `POST /sessions/:sessionId/start`

Starts a Baileys session for `sessionId` if one isn't already running.
Idempotent — calling it again while already `connected` or `qr_pending`
just returns the current state rather than restarting a healthy connection.

Response:

```json
{ "status": "qr_pending" | "connected" | "disconnected", "qr": "<base64 PNG>" | null }
```

`qr` is the raw base64 payload of a PNG (no `data:image/png;base64,`
prefix — add that on the frontend if you need an `<img src>`).

### `GET /sessions/:sessionId/status`

Read-only, same response shape as `/start`. Does **not** start a new session.
For an unknown/never-started `sessionId` returns:

```json
{ "status": "disconnected", "qr": null }
```

### `POST /sessions/:sessionId/send`

Body:

```json
{ "to": "5219991234567", "text": "Hello!" }
```

`to` is digits-only (no `+`); it's formatted internally as
`${to}@s.whatsapp.net`.

Response (HTTP 200 either way — failures are a controlled response, not a
server error):

```json
{ "success": true, "id": "<baileys message key id>" }
{ "success": false, "error": "<human-readable message>" }
```

### `POST /sessions/:sessionId/logout`

Logs out and clears the persisted auth state for that session, so a fresh
QR is issued the next time `/start` is called.

## Outbound webhook (this service → Laravel)

Configured via `LARAVEL_WEBHOOK_URL`, called with the same shared secret as
an `Authorization: Bearer` header. Failures to reach Laravel are logged and
swallowed — they never crash this service or drop the WhatsApp connection.

Message received from a contact:

```json
{
  "session_id": "<the sessionId>",
  "event": "message",
  "from": "5219991234567",
  "body": "message text",
  "external_message_id": "<baileys message key id>",
  "timestamp": 1234567890
}
```

Connection status change (QR issued, connection opened, connection closed):

```json
{ "session_id": "<the sessionId>", "event": "status", "status": "qr_pending" | "connected" | "disconnected" }
```

## Reconnect behavior

Follows the standard Baileys idiom: `connection.update` is inspected on
`close` events — `lastDisconnect.error`'s status code is checked against
`DisconnectReason`. Any reason other than `loggedOut` triggers an automatic
reconnect attempt (re-running the same session-start logic). `loggedOut`
clears that session's auth folder on disk and requires a fresh QR scan next
time `/start` is called.

## Deployment

This is meant to run as its own standalone, long-lived process — **not**
bundled into the Laravel app's build or deploy pipeline. Options:

- A Hostinger "Node.js app" deployment pointed at this folder.
- If that proves unstable for a persistent WebSocket-backed process, a
  small dedicated VPS running this under [PM2](https://pm2.keymetrics.io/)
  (`pm2 start index.js --name whatsapp-qr-service`) or a systemd service.

Whichever host is used, make sure `./sessions/` is on persistent disk (not
wiped on redeploy), since that's what lets sessions survive a restart
without re-scanning a QR code.
