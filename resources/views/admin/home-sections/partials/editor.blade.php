{{--
    EDITOR DE SECCIONES (modal crear/editar) — reutilizable.

    Lo usan la pantalla Secciones del Sitio (admin/home-sections/index) y
    cualquier otra vista del admin que necesite crear/editar una sección
    (p. ej. la pestaña "Bloques" de Productos).

    ── Cómo incluirlo ────────────────────────────────────────────────────
        @include('admin.home-sections.partials.editor')

    La vista debe extender admin.layouts.master (usa sus stacks `styles` y
    `scripts`, el picker de imágenes global y window.LinkPicker, que el
    layout ya carga con @vite). NO necesita variables: si no se le pasa
    `$editorData` las arma sola con
    App\Http\Controllers\Backend\HomeSectionController::editorData()
    (categorías raíz y árbol completo, marcas, colecciones y el mapa de tipos
    por página). Opcional: @include('...editor', ['editorData' => $data]).
    No usa las variables $categories/$brands/$collections de la vista que lo
    incluye (a propósito: en Productos tienen otra forma).

    Se puede incluir más de una vez en la misma página: el segundo include no
    repite estilos ni scripts (se protege con @once). Si se incluye dentro de
    un <form>, el modal se mueve a <body> al iniciar para no anidar formularios.

    ── Contrato JS ───────────────────────────────────────────────────────
        window.HomeSectionEditor.open({
            page:      'home' | 'collection' | 'product' (legado)
                     | 'product_template' | 'product_custom',
            zone:      'stack' | 'sidebar',   // bloques por producto; default 'stack'
            productId: 123,                   // product_custom nuevo: queda asignado a ese producto
            sectionId: 45,                    // editar (la página sale de la sección)
            onSaved:   (section, response) => {...},  // tras guardar OK (ya cerró el modal)
        });
        window.HomeSectionEditor.close();

    El editor NO recarga ni muestra toast de éxito: eso lo decide quien llama
    en onSaved. Endpoints que usa (permiso `home-sections`, create/edit):
        POST /admin/inicio-secciones/crear           (admin.home-sections.store)
        GET  /admin/inicio-secciones/editar/{id}     (admin.home-sections.edit)
        PUT  /admin/inicio-secciones/editar/{id}     (admin.home-sections.update)
        GET  /admin/inicio-secciones/productos/buscar (selección manual)
        GET  /admin/inicio-secciones/etiquetas/buscar (fuente "Por Etiqueta")
        GET  /admin/enlaces/buscar                   (destinos de enlace)

    ── Assets ────────────────────────────────────────────────────────────
    Vite (ya registrados en vite.config.js; no hay entry nuevo):
      - resources/css/admin/pages/home-sections.css (se carga aquí con @pushOnce)
      - resources/js/admin/link-picker.js  → window.LinkPicker.mountField(...)
        (lo carga admin.layouts.master para todo el admin)
      - resources/js/admin/image-picker.js → window.openImagePicker(...) (idem)
    El JS del editor es inline (partials/_editor_scripts.blade.php).
--}}
@php
    $editorData = $editorData ?? \App\Http\Controllers\Backend\HomeSectionController::editorData();
@endphp

@once
    @pushOnce('styles')
        @vite('resources/css/admin/pages/home-sections.css')
    @endPushOnce

    @include('admin.home-sections.partials._create_edit_modal', ['editorData' => $editorData])
    @include('admin.home-sections.partials._editor_scripts', ['editorData' => $editorData])
@endonce
