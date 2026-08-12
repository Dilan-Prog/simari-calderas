import { useEffect, useState } from 'react';

export default function TopBar({
  workflowName,
  isActive,
  canUndo,
  canRedo,
  saving,
  backUrl,
  onRename,
  onToggleActive,
  onUndo,
  onRedo,
  onSave,
  onRun,
  theme,
  onToggleTheme,
  userInitials,
}) {
  const [nameValue, setNameValue] = useState(workflowName);

  useEffect(() => {
    setNameValue(workflowName);
  }, [workflowName]);

  const commitRename = () => {
    if (nameValue !== workflowName) {
      onRename(nameValue);
    }
  };

  const handleKeyDown = (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      commitRename();
    }
  };

  let statusLabel = 'Inactivo';
  if (saving) {
    statusLabel = 'Guardando…';
  } else if (isActive) {
    statusLabel = 'Activo';
  }

  const initials = (userInitials || 'U').slice(0, 2).toUpperCase();

  return (
    <div className="wf-topbar">
      <div className="wf-topbar-left">
        <a href={backUrl} className="wf-topbar-back" title="Volver">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M15 18l-6-6 6-6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </a>

        <div className="wf-topbar-logo" aria-hidden="true" title="Automatización">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="5" cy="6" r="2.4" stroke="currentColor" strokeWidth="2" />
            <circle cx="5" cy="18" r="2.4" stroke="currentColor" strokeWidth="2" />
            <circle cx="18" cy="12" r="2.4" stroke="currentColor" strokeWidth="2" />
            <path d="M7.2 6.9L15.8 11" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
            <path d="M7.2 17.1L15.8 13" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
          </svg>
        </div>

        <input
          type="text"
          className="wf-topbar-name-input"
          value={nameValue}
          onChange={(event) => setNameValue(event.target.value)}
          onBlur={commitRename}
          onKeyDown={handleKeyDown}
        />

        <span
          className={`wf-topbar-status wf-topbar-status-${saving ? 'saving' : isActive ? 'active' : 'inactive'} ${isActive ? 'is-active' : ''}`}
        >
          <span className="wf-topbar-status-dot" />
          {statusLabel}
        </span>
      </div>

      <div className="wf-topbar-spacer" />

      <div className="wf-topbar-right">
        <div className="wf-topbar-history">
          <button
            type="button"
            className="wf-topbar-icon-btn"
            onClick={onUndo}
            disabled={!canUndo}
            title="Deshacer"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 14L4 9l5-5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
              <path d="M4 9h10a6 6 0 010 12h-1" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </button>

          <button
            type="button"
            className="wf-topbar-icon-btn"
            onClick={onRedo}
            disabled={!canRedo}
            title="Rehacer"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M15 14l5-5-5-5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
              <path d="M20 9H10a6 6 0 000 12h1" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </button>
        </div>

        <button
          type="button"
          className="wf-topbar-switch-group"
          onClick={onToggleActive}
          title={isActive ? 'Desactivar workflow' : 'Activar workflow'}
        >
          <span className={`wf-topbar-switch ${isActive ? 'is-on' : ''}`}>
            <span className="wf-topbar-switch-knob" />
          </span>
          <span className="wf-topbar-switch-label">{isActive ? 'Activo' : 'Inactivo'}</span>
        </button>

        <button
          type="button"
          className="wf-topbar-btn wf-topbar-btn-save"
          onClick={onSave}
          disabled={saving}
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" />
            <path d="M17 21v-8H7v8M7 3v5h8" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" />
          </svg>
          Guardar
        </button>

        <button
          type="button"
          className="wf-topbar-btn wf-topbar-btn-run"
          onClick={onRun}
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 4l14 8-14 8V4z" fill="currentColor" />
          </svg>
          Ejecutar
        </button>

        <button
          type="button"
          className="wf-topbar-theme-btn"
          onClick={onToggleTheme}
          title="Cambiar tema"
        >
          {theme === 'dark' ? (
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="5" stroke="currentColor" strokeWidth="2" />
              <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
            </svg>
          ) : (
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          )}
        </button>

        <div className="wf-topbar-avatar" title={initials}>
          {initials}
        </div>
      </div>
    </div>
  );
}
