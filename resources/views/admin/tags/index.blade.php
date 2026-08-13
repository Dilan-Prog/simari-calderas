@extends('admin.layouts.master')
@section('title')
    Etiquetas de Negocios - Admin
@endsection
@section('content')
    <div class="container user-manager">
        <section class="clients-manager-section">

            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; Negocios &gt; Etiquetas
                    </p>
                    <h1>Etiquetas de Negocios</h1>
                    <p class="breadcrumb-clients-manager main">Catálogo de etiquetas usadas para clasificar Negocios
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    @permiso('deals', 'create')
                    <button type="button" class="button-primary size-adjustment" id="btnNewTag">
                        + Nueva etiqueta
                    </button>
                    @endpermiso
                </div>
            </header>

            <main class="table-container-clients-manager" style="margin-top:20px;">
                <div class="table-scroll">
                    <table class="clients-manager-table">
                        <thead>
                            <tr>
                                <th>NOMBRE</th>
                                <th>COLOR</th>
                                <th>NEGOCIOS</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="tagsTableBody">
                            @forelse ($tags as $tag)
                                <tr class="tag-row" data-id="{{ $tag->id }}">
                                    <td>{{ $tag->name }}</td>
                                    <td>
                                        @if ($tag->color)
                                            <span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:{{ $tag->color }};vertical-align:middle;margin-right:6px;"></span>{{ $tag->color }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $tag->deals_count }}</td>
                                    <td>
                                        <div class="actions-container">
                                            @permiso('deals', 'edit')
                                            <button type="button" class="action-btn btn-edit-tag"
                                                data-id="{{ $tag->id }}" data-name="{{ $tag->name }}"
                                                data-color="{{ $tag->color }}" title="Editar">Editar</button>
                                            @endpermiso
                                            @permiso('deals', 'delete')
                                            <button type="button" class="action-btn btn-delete-tag"
                                                data-id="{{ $tag->id }}" data-name="{{ $tag->name }}"
                                                title="Eliminar">Eliminar</button>
                                            @endpermiso
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align:center; padding:40px; color:#6b7280;">
                                        No hay etiquetas registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </main>

            {{-- Modal simple crear/editar --}}
            <div id="tagModal" class="del-confirm-overlay">
                <div class="del-confirm-box ap-modal-box">
                    <div class="ap-modal-header">
                        <div class="ap-modal-header-text">
                            <h2 class="del-confirm-title" id="tagModalTitle">Nueva etiqueta</h2>
                        </div>
                        <button type="button" class="table-users-manager-action-btn cancel"
                            id="closeTagModal">✕</button>
                    </div>
                    <div id="tag-modal-errors" class="ap-modal-errors" style="display:none;"></div>
                    <form class="ap-modal-body" id="tagForm">
                        @csrf
                        <input type="hidden" name="tag_id" id="tagId">
                        <div class="ap-field-group">
                            <label class="supliers-manager-slider-label">Nombre <span
                                    style="color:red">*</span></label>
                            <input type="text" class="users-manager-input" name="name" id="tagName" maxlength="100">
                        </div>
                        <div class="ap-field-group" style="margin-top:12px;">
                            <label class="supliers-manager-slider-label">Color</label>
                            <input type="color" class="users-manager-input" name="color" id="tagColor"
                                value="#2563eb">
                        </div>
                        <div class="ap-modal-footer">
                            <button type="button" id="cancelTagModal"
                                class="button-secondary size-adjustment">Cancelar</button>
                            <button type="submit" class="button-primary size-adjustment" id="tagSubmitBtn">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                ?? document.querySelector('input[name="_token"]')?.value;
            const modal = document.getElementById('tagModal');
            const form = document.getElementById('tagForm');
            const errorsBox = document.getElementById('tag-modal-errors');
            const titleEl = document.getElementById('tagModalTitle');

            function openModal(tag) {
                errorsBox.style.display = 'none';
                errorsBox.innerHTML = '';
                if (tag) {
                    titleEl.textContent = 'Editar etiqueta';
                    document.getElementById('tagId').value = tag.id;
                    document.getElementById('tagName').value = tag.name;
                    document.getElementById('tagColor').value = tag.color || '#2563eb';
                } else {
                    titleEl.textContent = 'Nueva etiqueta';
                    form.reset();
                    document.getElementById('tagId').value = '';
                }
                modal.classList.add('active');
                modal.style.display = 'flex';
            }

            function closeModal() {
                modal.classList.remove('active');
                modal.style.display = 'none';
            }

            document.getElementById('btnNewTag')?.addEventListener('click', () => openModal(null));
            document.getElementById('closeTagModal')?.addEventListener('click', closeModal);
            document.getElementById('cancelTagModal')?.addEventListener('click', closeModal);

            document.querySelectorAll('.btn-edit-tag').forEach((btn) => {
                btn.addEventListener('click', () => openModal({
                    id: btn.dataset.id,
                    name: btn.dataset.name,
                    color: btn.dataset.color,
                }));
            });

            document.querySelectorAll('.btn-delete-tag').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    if (!confirm(`¿Eliminar la etiqueta "${btn.dataset.name}"?`)) return;

                    const res = await fetch(`{{ url('admin/etiquetas-negocio') }}/${btn.dataset.id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    });

                    if (res.ok) window.location.reload();
                });
            });

            form?.addEventListener('submit', async (e) => {
                e.preventDefault();

                const id = document.getElementById('tagId').value;
                const url = id
                    ? `{{ url('admin/etiquetas-negocio') }}/${id}`
                    : `{{ url('admin/etiquetas-negocio') }}`;

                const res = await fetch(url, {
                    method: id ? 'PUT' : 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        name: document.getElementById('tagName').value,
                        color: document.getElementById('tagColor').value,
                    }),
                });

                if (res.ok) {
                    window.location.reload();
                    return;
                }

                const data = await res.json();
                errorsBox.style.display = 'block';
                errorsBox.innerHTML = Object.values(data.errors || {}).flat().join('<br>');
            });
        })();
    </script>
@endpush
