import React, { useCallback, useEffect, useState } from 'react';
import Header from './Header';
import Tabs from './Tabs';
import TemplatesTab from './tabs/TemplatesTab';
import ListsTab from './tabs/ListsTab';
import CampaignsTab from './tabs/CampaignsTab';
import SequencesTab from './tabs/SequencesTab';
import templatesApi from './api/templatesApi';
import listsApi from './api/listsApi';
import campaignsApi from './api/campaignsApi';
import sequencesApi from './api/sequencesApi';

/**
 * Componente raíz de la SPA de Email Marketing. Una sola página con 4
 * pestañas, sin recarga entre ellas (mockup completo). El estado `sub`
 * (formulario/detalle abierto) vive DENTRO de cada componente de pestaña,
 * no aquí -- cada Tab monta/desmonta según `tab === activo`, así que su
 * propio useState se reinicia naturalmente al salir y volver a entrar.
 *
 * El botón primario del Header ("Nueva plantilla"/"Nueva lista"/…) no
 * conoce el estado interno de cada pestaña: solo incrementa un contador
 * (`createSignal`) que la pestaña activa observa para abrir su propio
 * flujo de creación. Ver `useCreateSignal` en cada Tab.
 */
export default function EmailMarketingApp({ templatesUrl, listsUrl, campaignsUrl, sequencesUrl, userInitials }) {
  const [tab, setTab] = useState('plantillas');
  const [counts, setCounts] = useState({ plantillas: 0, listas: 0, campanas: 0, secuencias: 0 });
  const [createSignal, setCreateSignal] = useState(0);

  const refreshCounts = useCallback(() => {
    Promise.all([
      templatesApi.list().catch(() => null),
      listsApi.list().catch(() => null),
      campaignsApi.list().catch(() => null),
      sequencesApi.list().catch(() => []),
    ]).then(([templates, lists, campaigns, sequences]) => {
      setCounts({
        plantillas: templates && templates.data ? templates.data.total : 0,
        listas: lists && lists.data ? lists.data.total : 0,
        campanas: campaigns && campaigns.data ? campaigns.data.total : 0,
        secuencias: Array.isArray(sequences) ? sequences.length : 0,
      });
    });
  }, []);

  useEffect(() => {
    refreshCounts();
  }, [refreshCounts]);

  const handlePrimaryAction = () => setCreateSignal((n) => n + 1);

  return (
    <div className="em-shell">
      <Header activeTab={tab} onPrimaryAction={handlePrimaryAction} />

      <div className="em-card">
        <Tabs active={tab} onChange={setTab} counts={counts} />

        {tab === 'plantillas' && (
          <TemplatesTab createSignal={createSignal} onChanged={refreshCounts} userInitials={userInitials} />
        )}
        {tab === 'listas' && <ListsTab createSignal={createSignal} onChanged={refreshCounts} />}
        {tab === 'campanas' && (
          <CampaignsTab createSignal={createSignal} onChanged={refreshCounts} onGoToTab={setTab} />
        )}
        {tab === 'secuencias' && <SequencesTab createSignal={createSignal} onChanged={refreshCounts} />}
      </div>

      <div className="em-future-note">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.1">
          <circle cx="12" cy="12" r="9.2" />
          <path d="M12 11v5.5M12 7.6v.6" strokeLinecap="round" />
        </svg>
        <div>
          <div className="em-future-note-title">Mejoras futuras sugeridas, hoy no disponibles</div>
          <div className="em-future-note-desc">
            Importación de contactos por CSV · pruebas A/B operativas · condiciones y bifurcaciones en secuencias ·
            doble opt-in · panel de analítica comparada entre campañas.
          </div>
        </div>
      </div>
    </div>
  );
}
