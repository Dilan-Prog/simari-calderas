<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD modal-based del catálogo de etiquetas de Negocios (Fase 14 del plan
 * CRM), mismo patrón que CredentialController — sin páginas propias de
 * create/edit, todo vía index + modal, respuestas JSON.
 */
class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('deals')->orderBy('name')->get();

        return view('admin.tags.index', compact('tags'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100|unique:tags,name',
            'color' => 'nullable|string|max:20',
        ]);

        $tag = Tag::create($data);

        return response()->json(['success' => true, 'tag' => $tag]);
    }

    public function edit(Tag $tag)
    {
        return response()->json([
            'id'    => $tag->id,
            'name'  => $tag->name,
            'color' => $tag->color,
        ]);
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100', Rule::unique('tags', 'name')->ignore($tag->id)],
            'color' => 'nullable|string|max:20',
        ]);

        $tag->update($data);

        return response()->json(['success' => true, 'tag' => $tag]);
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return response()->json(['success' => true]);
    }
}
