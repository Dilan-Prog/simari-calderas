<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\HomeSection;
use App\Models\HomeSectionSlide;
use Illuminate\Http\Request;

class HomeSectionController extends Controller
{
    protected array $types = [
        'hero_slider', 'banner', 'dual_banner', 'product_carousel',
        'category_grid', 'brand_carousel', 'html_block',
    ];

    public function index()
    {
        $sections = HomeSection::orderBy('sort_order')->orderBy('id')->get();
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $collections = Collection::where('is_active', true)->orderBy('name')->get();

        return view('admin.home-sections.index', compact('sections', 'categories', 'brands', 'collections'));
    }

    protected function buildConfig(Request $request, string $type): ?array
    {
        switch ($type) {
            case 'product_carousel':
                return [
                    'source'        => $request->input('source', 'featured'),
                    'category_id'   => $request->input('category_id') ?: null,
                    'brand_id'      => $request->input('brand_id') ?: null,
                    'collection_id' => $request->input('collection_id') ?: null,
                    'product_ids'   => array_values(array_filter((array) $request->input('product_ids', []))),
                    'limit'         => $request->input('limit') !== null ? (int) $request->input('limit') : null,
                ];

            case 'banner':
                return [
                    'image_url' => $request->input('banner_image_url'),
                    'link_url'  => $request->input('banner_link_url'),
                    'alt'       => $request->input('banner_alt'),
                ];

            case 'dual_banner':
                return [
                    'left' => [
                        'image_url' => $request->input('left_image_url'),
                        'link_url'  => $request->input('left_link_url'),
                        'alt'       => $request->input('left_alt'),
                    ],
                    'right' => [
                        'image_url' => $request->input('right_image_url'),
                        'link_url'  => $request->input('right_link_url'),
                        'alt'       => $request->input('right_alt'),
                    ],
                ];

            case 'category_grid':
                return [
                    'category_ids' => array_values(array_filter((array) $request->input('category_ids', []))),
                ];

            case 'brand_carousel':
                return [];

            case 'html_block':
                return [
                    'html' => $request->input('html'),
                ];

            case 'hero_slider':
            default:
                return null;
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'type'       => 'required|string|in:' . implode(',', $this->types),
            'title'      => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $section = new HomeSection();
        $section->type       = $request->type;
        $section->title      = $request->title ?? null;
        $section->config     = $this->buildConfig($request, $request->type);
        $section->sort_order = $request->sort_order ?? 0;
        $section->is_active  = $request->boolean('is_active', true);
        $section->save();

        return response()->json([
            'success' => true,
            'section' => $section,
        ]);
    }

    public function edit(string $id)
    {
        $section = HomeSection::findOrFail($id);
        return response()->json($section);
    }

    public function update(Request $request, string $id)
    {
        $section = HomeSection::findOrFail($id);

        $request->validate([
            'type'       => 'required|string|in:' . implode(',', $this->types),
            'title'      => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $section->type       = $request->type;
        $section->title      = $request->title ?? null;
        $section->config     = $this->buildConfig($request, $request->type);
        $section->sort_order = $request->sort_order ?? 0;
        $section->is_active  = $request->boolean('is_active', true);
        $section->save();

        return response()->json([
            'success' => true,
            'section' => $section,
        ]);
    }

    public function destroy(string $id)
    {
        $section = HomeSection::findOrFail($id);
        $section->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:home_sections,id',
        ]);

        $sections = HomeSection::whereIn('id', $request->order)
            ->get()
            ->keyBy('id');

        if ($sections->count() !== count($request->order)) {
            return response()->json([
                'success' => false,
                'message' => 'El orden recibido no coincide con las secciones existentes.',
            ], 422);
        }

        foreach (array_values($request->order) as $i => $sectionId) {
            $sections[$sectionId]->update(['sort_order' => $i]);
        }

        return response()->json(['success' => true]);
    }

    public function slidesPage(string $homeSection)
    {
        $section = HomeSection::with('slides')->findOrFail($homeSection);

        return view('admin.home-sections.slides', compact('section'));
    }

    public function slides(string $homeSection)
    {
        $section = HomeSection::findOrFail($homeSection);
        $slides = $section->slides()->get();

        return response()->json($slides);
    }

    public function storeSlide(Request $request, string $homeSection)
    {
        $section = HomeSection::findOrFail($homeSection);

        $request->validate([
            'image_url'       => 'required|string|max:255',
            'badge_text'      => 'nullable|string|max:120',
            'title'           => 'required|string|max:255',
            'title_highlight' => 'nullable|string|max:120',
            'description'     => 'nullable|string',
            'link_url'        => 'nullable|string|max:255',
            'sort_order'      => 'nullable|integer|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        $slide = new HomeSectionSlide();
        $slide->home_section_id = $section->id;
        $slide->image_url       = $request->image_url;
        $slide->badge_text      = $request->badge_text ?? null;
        $slide->title           = $request->title;
        $slide->title_highlight = $request->title_highlight ?? null;
        $slide->description     = $request->description ?? null;
        $slide->link_url        = $request->link_url ?? null;
        $slide->sort_order      = $request->sort_order ?? ($section->slides()->count());
        $slide->is_active       = $request->boolean('is_active', true);
        $slide->save();

        return response()->json([
            'success' => true,
            'slide'   => $slide,
        ]);
    }

    public function updateSlide(Request $request, string $homeSection, string $slide)
    {
        $section = HomeSection::findOrFail($homeSection);
        $slideModel = HomeSectionSlide::where('home_section_id', $section->id)->findOrFail($slide);

        $request->validate([
            'image_url'       => 'required|string|max:255',
            'badge_text'      => 'nullable|string|max:120',
            'title'           => 'required|string|max:255',
            'title_highlight' => 'nullable|string|max:120',
            'description'     => 'nullable|string',
            'link_url'        => 'nullable|string|max:255',
            'sort_order'      => 'nullable|integer|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        $slideModel->image_url       = $request->image_url;
        $slideModel->badge_text      = $request->badge_text ?? null;
        $slideModel->title           = $request->title;
        $slideModel->title_highlight = $request->title_highlight ?? null;
        $slideModel->description     = $request->description ?? null;
        $slideModel->link_url        = $request->link_url ?? null;
        $slideModel->sort_order      = $request->sort_order ?? $slideModel->sort_order;
        $slideModel->is_active       = $request->boolean('is_active', true);
        $slideModel->save();

        return response()->json([
            'success' => true,
            'slide'   => $slideModel,
        ]);
    }

    public function destroySlide(string $homeSection, string $slide)
    {
        $section = HomeSection::findOrFail($homeSection);
        $slideModel = HomeSectionSlide::where('home_section_id', $section->id)->findOrFail($slide);
        $slideModel->delete();

        return response()->json(['success' => true]);
    }

    public function reorderSlides(Request $request, string $homeSection)
    {
        $section = HomeSection::findOrFail($homeSection);

        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:home_section_slides,id',
        ]);

        $slides = HomeSectionSlide::whereIn('id', $request->order)
            ->where('home_section_id', $section->id)
            ->get()
            ->keyBy('id');

        if ($slides->count() !== count($request->order)) {
            return response()->json([
                'success' => false,
                'message' => 'El orden recibido no coincide con los slides de esta sección.',
            ], 422);
        }

        foreach (array_values($request->order) as $i => $slideId) {
            $slides[$slideId]->update(['sort_order' => $i]);
        }

        return response()->json(['success' => true]);
    }
}
