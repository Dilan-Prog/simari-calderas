@extends('admin.layouts.master')

@section('title')
    {{ $customer->first_name }} {{ $customer->last_name }} - Admin
@endsection

@section('content')
    <div class="container user-manager show-information">
        <section class="clients-manager-section show">

            {{-- Back link --}}
            <div class="link-container-show">
                <a class="client-window-link" href="{{ route('admin.clients.index') }}">
                    <span>&lt;</span> &nbsp; Clientes
                </a>
            </div>

            {{-- Header --}}
            <div class="client-header-show">
                <div class="client-left-show">
                    <div class="avatar-show">
                        {{ strtoupper(substr($customer->first_name, 0, 1)) }}
                    </div>
                    <div class="information-client-show">
                        <h2>{{ $customer->first_name }} {{ $customer->last_name }}</h2>
                        <p class="breadcrumb-users-manager">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-building2">
                                <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                                <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                                <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                                <path d="M10 6h4"></path><path d="M10 10h4"></path>
                                <path d="M10 14h4"></path><path d="M10 18h4"></path>
                            </svg>
                            {{ $customer->company ?? '—' }}
                        </p>
                        @php $addr = $customer->customer_addresses->first(); @endphp
                        @if ($addr)
                            <p class="breadcrumb-users-manager">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-map-pin">
                                    <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                {{ $addr->city }}, {{ $addr->state }}
                            </p>
                        @endif
                        @php
                            $statusLabel = match($customer->status) {
                                'active'    => 'Activo',
                                'inactive'  => 'Inactivo',
                                'suspended' => 'Suspendido',
                                default     => $customer->status,
                            };
                            $statusClass = $customer->status === 'active' ? 'status' : 'status-inactive';
                        @endphp
                        <span class="users-manager-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                </div>

                <div class="client-actions-show up">
                    <div class="client-actions-show">
                        <button type="button"
                            class="button-primary size-adjustment edit-client btn-edit-client-show"
                            data-id="{{ $customer->id }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-pen">
                                <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path>
                            </svg>
                            Editar Cliente
                        </button>
                        <button type="button" class="button-primary size-adjustment new-order">
                            ＋ Nueva Orden
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="tabs-show-clients-container">
                <button class="tab-show-client active" data-tab="info">Información</button>
                <button class="tab-show-client" data-tab="orders">Órdenes</button>
                <button class="tab-show-client" data-tab="addresses">Direcciones</button>
            </div>

            {{-- Tab: Info --}}
            <div class="tab-content-show-client active" id="info">
                <div class="grid-2-information-client-show">

                    <div class="card-information-show">
                        <h3>Información de Contacto</h3>

                        <p class="breadcrumb-users-manager main"><strong>Email</strong></p>
                        <p class="breadcrumb-users-manager">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-mail">
                                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                            &nbsp; {{ $customer->email ?? '—' }}
                        </p>

                        <p class="breadcrumb-users-manager main"><strong>Teléfono</strong></p>
                        <p class="breadcrumb-users-manager">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-phone">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            &nbsp; {{ $customer->phone ?? '—' }}
                        </p>

                        <p class="breadcrumb-users-manager main"><strong>RFC</strong></p>
                        <p class="breadcrumb-users-manager">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-file-text">
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                                <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                <path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path>
                            </svg>
                            &nbsp; {{ $customer->rfc ?? '—' }}
                        </p>

                        <p class="breadcrumb-users-manager main"><strong>Tipo de documento</strong></p>
                        <p class="breadcrumb-users-manager">&nbsp; {{ strtoupper($customer->document_type ?? '—') }}</p>

                        <p class="breadcrumb-users-manager main"><strong>Número de documento</strong></p>
                        <p class="breadcrumb-users-manager">&nbsp; {{ $customer->document_numer ?? '—' }}</p>

                        <p class="breadcrumb-users-manager main"><strong>Fecha de nacimiento</strong></p>
                        <p class="breadcrumb-users-manager">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-calendar">
                                <path d="M8 2v4"></path><path d="M16 2v4"></path>
                                <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                <path d="M3 10h18"></path>
                            </svg>
                            &nbsp;
                            @if ($customer->birth_date)
                                {{ \Carbon\Carbon::parse($customer->birth_date)->translatedFormat('j \d\e F, Y') }}
                            @else
                                —
                            @endif
                        </p>

                        <p class="breadcrumb-users-manager main"><strong>¿Cómo nos conoció?</strong></p>
                        <p class="breadcrumb-users-manager">&nbsp; {{ ucfirst($customer->source ?? '—') }}</p>

                        <p class="breadcrumb-users-manager main"><strong>Fecha de registro</strong></p>
                        <p class="breadcrumb-users-manager">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-calendar">
                                <path d="M8 2v4"></path><path d="M16 2v4"></path>
                                <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                <path d="M3 10h18"></path>
                            </svg>
                            &nbsp; {{ $customer->created_at?->translatedFormat('j \d\e F \d\e Y') ?? '—' }}
                        </p>
                    </div>

                    <div class="notes-information-show">
                        <div class="card-information-show notes">
                            <h3>Notas Internas</h3>
                            <div class="note-box-information-client">
                                <p class="breadcrumb-users-manager main">
                                    {{ $customer->notes ?? 'Sin notas.' }}
                                </p>
                            </div>
                        </div>
                        <div class="card-information-show activitie">
                            <h3>Última Actividad</h3>
                            <p class="breadcrumb-users-manager main activitie">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-calendar">
                                    <path d="M8 2v4"></path><path d="M16 2v4"></path>
                                    <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                    <path d="M3 10h18"></path>
                                </svg>
                                {{ $customer->updated_at?->translatedFormat('j \d\e F, Y') ?? '—' }}
                            </p>
                            <p class="breadcrumb-users-manager">Sin actividad registrada.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab: Orders --}}
            <div class="tab-content-show-client" id="orders">
                <div class="stats">
                    <div class="stat-card">
                        <p class="breadcrumb-users-manager">Total de Órdenes</p>
                        <h2>0</h2>
                    </div>
                    <div class="stat-card">
                        <p class="breadcrumb-users-manager">Total Gastado</p>
                        <h2>$0.00</h2>
                    </div>
                    <div class="stat-card">
                        <p class="breadcrumb-users-manager">Última Orden</p>
                        <h2>—</h2>
                    </div>
                </div>
                <main class="table-container-users-manager">
                    <table class="users-manager-table">
                        <thead>
                            <tr>
                                <th>ORDEN #</th>
                                <th>FECHA</th>
                                <th>ESTADO</th>
                                <th>TOTAL</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                    </table>
                    <div class="table-scroll">
                        <table class="users-manager-table">
                            <tbody>
                                <tr>
                                    <td colspan="5">
                                        <p class="breadcrumb-users-manager main" style="text-align:center;padding:20px;">
                                            Sin órdenes registradas.
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </main>
            </div>

            {{-- Tab: Addresses --}}
            <div class="tab-content-show-client" id="addresses">
                <div class="grid-2-information-client-show">
                    @forelse ($customer->customer_addresses as $address)
                        <div class="card-information-show">
                            <div class="card-information-show-header" style="display:flex;justify-content:space-between;align-items:center;">
                                <h3>{{ ucfirst($address->label ?? 'Dirección') }}</h3>
                                @if ($address->is_default)
                                    <span class="badge blue">Predeterminada</span>
                                @endif
                            </div>
                            <p class="breadcrumb-users-manager main"><strong>Destinatario</strong></p>
                            <p class="breadcrumb-users-manager">{{ $address->recipient_name ?? '—' }}</p>
                            <p class="breadcrumb-users-manager main"><strong>Dirección</strong></p>
                            <p class="breadcrumb-users-manager">
                                {{ $address->address_line1 }}
                                @if ($address->address_line2), {{ $address->address_line2 }}@endif
                                @if ($address->city), {{ $address->city }}@endif
                                @if ($address->state), {{ $address->state }}@endif
                                @if ($address->postal_code), {{ $address->postal_code }}@endif
                            </p>
                            <p class="breadcrumb-users-manager main"><strong>Teléfono</strong></p>
                            <p class="breadcrumb-users-manager">{{ $address->phone ?? '—' }}</p>
                        </div>
                    @empty
                        <p class="breadcrumb-users-manager main">Sin direcciones registradas.</p>
                    @endforelse
                </div>
                <button type="button" class="btn-dashed">＋ Agregar dirección</button>
            </div>

        </section>

        {{-- Edit modal --}}
        @include('admin.client.partials._edit_modal')

        <script>
            // Tab switching
            document.querySelectorAll('.tab-show-client').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.tab-show-client').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.tab-content-show-client').forEach(c => c.classList.remove('active'));
                    btn.classList.add('active');
                    document.getElementById(btn.dataset.tab).classList.add('active');
                });
            });

            // Edit client button in show page
            const editClientModal   = document.getElementById('clientEditModal');
            const editClientForm    = document.getElementById('clientEditForm');
            const editClientUrl     = '{{ url('/admin/clientes/editar-cliente') }}';
            let currentClientId     = null;

            const closeEditWithAnim = () => {
                const content = editClientModal.querySelector('.user-manager-modal-content');
                if (content) {
                    content.style.transition = 'transform 0.2s ease-in';
                    content.style.transform  = 'translateX(100%)';
                }
                editClientModal.style.transition = 'opacity 0.2s ease-in';
                editClientModal.style.opacity    = '0';
                setTimeout(() => {
                    editClientModal.style.display    = 'none';
                    editClientModal.style.opacity    = '';
                    editClientModal.style.transition = '';
                    if (content) { content.style.transform = ''; content.style.transition = ''; }
                }, 200);
            };

            document.querySelector('.btn-edit-client-show').addEventListener('click', () => {
                const id = '{{ $customer->id }}';
                currentClientId = id;

                fetch(editClientUrl + '/' + id, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                })
                .then(res => res.json())
                .then(customer => {
                    const addr = customer.customer_addresses?.[0] ?? null;

                    editClientForm.querySelector('[name="full_name"]').value      = [customer.first_name, customer.last_name].filter(Boolean).join(' ');
                    editClientForm.querySelector('[name="company"]').value        = customer.company       ?? '';
                    editClientForm.querySelector('[name="email"]').value          = customer.email         ?? '';
                    editClientForm.querySelector('[name="phone"]').value          = customer.phone         ?? '';
                    editClientForm.querySelector('[name="rfc"]').value            = customer.rfc           ?? '';
                    editClientForm.querySelector('[name="document_type"]').value  = customer.document_type ?? '';
                    editClientForm.querySelector('[name="document_numer"]').value = customer.document_numer ?? '';
                    editClientForm.querySelector('[name="birth_date"]').value     = customer.birth_date    ?? '';
                    editClientForm.querySelector('[name="source"]').value         = customer.source        ?? '';
                    editClientForm.querySelector('[name="status"]').value         = customer.status        ?? 'active';
                    editClientForm.querySelector('[name="address_line1"]').value  = addr?.address_line1    ?? '';
                    editClientForm.querySelector('[name="address_line2"]').value  = addr?.address_line2    ?? '';
                    editClientForm.querySelector('[name="city"]').value           = addr?.city             ?? '';
                    editClientForm.querySelector('[name="state"]').value          = addr?.state            ?? '';
                    editClientForm.querySelector('[name="postal_code"]').value    = addr?.postal_code      ?? '';
                    editClientForm.querySelector('[name="country"]').value        = addr?.country          ?? '';
                    editClientForm.querySelector('[name="reference"]').value      = addr?.reference        ?? '';

                    editClientModal.style.display = 'flex';
                })
                .catch(err => console.error('Error loading client:', err));
            });

            document.getElementById('closeClientEditModal').addEventListener('click',  () => closeEditWithAnim());
            document.getElementById('cancelClientEditModal').addEventListener('click', () => closeEditWithAnim());
            editClientModal.addEventListener('click', (e) => {
                if (e.target === editClientModal) closeEditWithAnim();
            });

            // Same as fiscal
            const editSameAsFiscal = document.getElementById('editSameAsFiscal');
            const editShippingAddr = document.getElementById('editShippingAddress');
            const editFiscalAddr   = document.getElementById('editFiscalAddress');

            editSameAsFiscal.addEventListener('change', () => {
                editShippingAddr.disabled = editSameAsFiscal.checked;
                if (editSameAsFiscal.checked) editShippingAddr.value = editFiscalAddr.value;
            });
            editFiscalAddr.addEventListener('input', () => {
                if (editSameAsFiscal.checked) editShippingAddr.value = editFiscalAddr.value;
            });

            // Submit edit form
            editClientForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const errorsContainer = document.getElementById('client-edit-errors');
                errorsContainer.style.display = 'none';
                errorsContainer.innerHTML = '';

                const formData = new FormData(editClientForm);
                formData.append('_method', 'PUT');

                try {
                    const response = await fetch(editClientUrl + '/' + currentClientId, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                    const data = await response.json();
                    if (response.ok) {
                        closeEditWithAnim();
                        setTimeout(() => window.location.reload(), 200);
                    } else if (response.status === 422) {
                        const errorList = Object.values(data.errors).flat();
                        errorsContainer.innerHTML     = errorList.map(msg => `<p>${msg}</p>`).join('');
                        errorsContainer.style.display = 'block';
                    }
                } catch (err) {
                    console.error('Error updating client:', err);
                }
            });
        </script>
    </div>
@endsection
