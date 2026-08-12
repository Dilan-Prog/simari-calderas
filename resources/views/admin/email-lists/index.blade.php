@extends('admin.layouts.master')

@section('title', 'Listas de Correo - Admin')

@push('styles')
<style>
:root {
    --background--white:          #ffffff;
    --header-footer-color:        #1A2535;
    --text-subwhite-color:        #D1D5DC;
    --text-description-color:     #6B7280;
    --secondary-color:            #ff6213;
    --button-primary-color:       #ff6213;
    --button-primary-color-hover: #de4a00;
    --font-family:                'Inter', sans-serif;
    --shadow-sm:                  0 1px 2px rgba(0,0,0,.06);
    --shadow-md:                  0 10px 20px rgba(0,0,0,.1);
}

.el-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }

.el-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.el-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.el-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.el-breadcrumb-current { color: #374151; }
.el-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.el-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0; }
.el-header-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; flex-wrap: wrap; }
.el-btn-new { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: var(--button-primary-color); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; white-space: nowrap; flex-shrink: 0; }
.el-btn-new:hover { background: var(--button-primary-color-hover); color: #fff; }

.el-table-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
.el-table-scroll { overflow-x: auto; }
.el-table { width: 100%; border-collapse: collapse; min-width: 700px; }
.el-table thead tr { background: var(--header-footer-color); height: 44px; }
.el-table thead th { padding: 0 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subwhite-color); white-space: nowrap; }
.el-table tbody tr { height: 56px; border-bottom: 1px solid #F3F4F6; transition: background .15s; }
.el-table tbody tr:last-child { border-bottom: none; }
.el-table tbody tr:hover { background: rgba(255,247,237,.4); }
.el-table td { padding: 0 20px; vertical-align: middle; }
.el-td-name { font-size: 14px; color: #111827; font-weight: 500; }
.el-td-name a { color: inherit; text-decoration: none; }
.el-td-name a:hover { color: var(--secondary-color); text-decoration: underline; }
.el-type-pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; white-space: nowrap; }
.el-type-pill-static { background: #EFF6FF; color: #3B82F6; border: 1px solid #BFDBFE; }
.el-type-pill-active { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }
.el-count { font-size: 14px; font-weight: 600; color: #111827; font-variant-numeric: tabular-nums; }
.el-count-label { font-size: 12px; color: var(--text-description-color); font-weight: 400; margin-left: 4px; }

.el-actions { display: flex; align-items: center; gap: 4px; }
.el-action-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; display: inline-flex; align-items: center; justify-content: center; color: var(--text-description-color); text-decoration: none; cursor: pointer; transition: background .15s, color .15s; padding: 0; }
.el-action-btn:hover { background: #F3F4F6; color: var(--secondary-color); }
.el-action-btn-delete:hover { background: #FEF2F2 !important; color: #DC2626 !important; }

.el-empty-row td { padding: 48px 20px; text-align: center; }
.el-empty-inner { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #9CA3AF; }
.el-empty-inner svg { opacity: .4; }
.el-empty-inner p { margin: 0; font-size: 14px; }

.el-flash { border-radius: 8px; padding: 12px 16px; font-size: 13px; margin-bottom: 0; }
.el-flash-success { background: #D1FAE5; border: 1px solid #6EE7B7; color: #065F46; }

@media (max-width: 640px) {
    .el-page { padding: 16px; gap: 16px; }
    .el-header { flex-direction: column; align-items: stretch; }
    .el-btn-new { justify-content: center; }
}
</style>
@endpush

@section('content')
<div class="el-page">

    <div class="el-header">
        <div>
            <div class="el-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="el-breadcrumb-current">Listas de Correo</span>
            </div>
            <h1 class="el-title">Listas de correo</h1>
            <p class="el-subtitle">Listas estáticas (miembros fijos) y dinámicas (filtro de clientes) para campañas de email marketing</p>
        </div>
        <div class="el-header-actions">
            <a href="{{ route('admin.email-lists.create') }}" class="el-btn-new">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Nueva lista
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="el-flash el-flash-success">{{ session('success') }}</div>
    @endif

    <div class="el-table-card">
        <div class="el-table-scroll">
            <table class="el-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Miembros</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($emailLists as $emailList)
                    <tr>
                        <td class="el-td-name">
                            <a href="{{ route('admin.email-lists.edit', $emailList) }}">{{ $emailList->name }}</a>
                        </td>
                        <td>
                            @if($emailList->type === 'static')
                                <span class="el-type-pill el-type-pill-static">Estática</span>
                            @else
                                <span class="el-type-pill el-type-pill-active">Dinámica</span>
                            @endif
                        </td>
                        <td>
                            @if($emailList->type === 'static')
                                <span class="el-count">{{ $emailList->members_count }}</span>
                                <span class="el-count-label">miembros</span>
                            @else
                                <span class="el-count-label">Calculado por filtro</span>
                            @endif
                        </td>
                        <td>
                            <div class="el-actions">
                                <a href="{{ route('admin.email-lists.edit', $emailList) }}" class="el-action-btn" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </a>
                                <form action="{{ route('admin.email-lists.destroy', $emailList) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar la lista &quot;{{ $emailList->name }}&quot;? Esta acción no se puede deshacer.');" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="el-action-btn el-action-btn-delete" title="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="el-empty-row">
                        <td colspan="4">
                            <div class="el-empty-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <p>Aún no hay listas de correo creadas.</p>
                                <a href="{{ route('admin.email-lists.create') }}" style="font-size:13px;color:#ff6213;text-decoration:none;">Crear la primera lista</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($emailLists->hasPages())
        <div>{{ $emailLists->links() }}</div>
    @endif

</div>
@endsection
