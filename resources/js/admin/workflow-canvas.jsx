import { createRoot } from 'react-dom/client';
import WorkflowCanvasApp from './canvas/WorkflowCanvasApp.jsx';

const el = document.getElementById('workflow-canvas-root');

if (el) {
  const workflowId = el.dataset.workflowId;
  const canvasUrl = el.dataset.canvasUrl;
  const stepsUrl = el.dataset.stepsUrl;
  const layoutUrl = el.dataset.layoutUrl;
  const testUrl = el.dataset.testUrl;
  const workflowName = el.dataset.workflowName;
  const workflowActive = el.dataset.workflowActive === '1';
  const updateUrl = el.dataset.updateUrl;
  const toggleUrl = el.dataset.toggleUrl;
  const undoUrl = el.dataset.undoUrl;
  const redoUrl = el.dataset.redoUrl;
  const variablesUrl = el.dataset.variablesUrl;
  const notesUrl = el.dataset.notesUrl;
  const executionsUrl = el.dataset.executionsUrl;
  const backUrl = el.dataset.backUrl;
  const userInitials = el.dataset.userInitials;

  const root = createRoot(el);
  root.render(
    <WorkflowCanvasApp
      workflowId={workflowId}
      userInitials={userInitials}
      canvasUrl={canvasUrl}
      stepsUrl={stepsUrl}
      layoutUrl={layoutUrl}
      testUrl={testUrl}
      workflowName={workflowName}
      workflowActive={workflowActive}
      updateUrl={updateUrl}
      toggleUrl={toggleUrl}
      undoUrl={undoUrl}
      redoUrl={redoUrl}
      variablesUrl={variablesUrl}
      notesUrl={notesUrl}
      executionsUrl={executionsUrl}
      backUrl={backUrl}
    />
  );
}
