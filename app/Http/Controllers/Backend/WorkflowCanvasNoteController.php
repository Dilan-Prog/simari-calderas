<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use App\Models\WorkflowCanvasNote;
use Illuminate\Http\Request;

class WorkflowCanvasNoteController extends Controller
{
    public function index(Workflow $workflow)
    {
        $notes = WorkflowCanvasNote::where('workflow_id', $workflow->id)->get();

        return response()->json($notes);
    }

    public function store(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'text' => 'nullable|string',
            'position_x' => 'nullable|integer',
            'position_y' => 'nullable|integer',
        ]);

        $note = new WorkflowCanvasNote();
        $note->workflow_id = $workflow->id;
        $note->text = $data['text'] ?? null;
        $note->position_x = $data['position_x'] ?? 0;
        $note->position_y = $data['position_y'] ?? 0;
        $note->save();

        return response()->json([
            'success' => true,
            'note' => $note,
        ]);
    }

    // $workflow no se usa directamente aquí, pero se declara por el mismo
    // motivo del bug ya conocido en este proyecto sobre rutas con dos
    // segmentos de modelo (route model binding implícito requiere que
    // ambos parámetros de la ruta estén tipados en la firma).
    public function update(Request $request, Workflow $workflow, WorkflowCanvasNote $note)
    {
        $data = $request->validate([
            'text' => 'sometimes|nullable|string',
            'position_x' => 'sometimes|nullable|integer',
            'position_y' => 'sometimes|nullable|integer',
        ]);

        if (array_key_exists('text', $data)) {
            $note->text = $data['text'];
        }
        if (array_key_exists('position_x', $data)) {
            $note->position_x = $data['position_x'];
        }
        if (array_key_exists('position_y', $data)) {
            $note->position_y = $data['position_y'];
        }
        $note->save();

        return response()->json([
            'success' => true,
            'note' => $note,
        ]);
    }

    public function destroy(Workflow $workflow, WorkflowCanvasNote $note)
    {
        $note->delete();

        return response()->json(['success' => true]);
    }
}
