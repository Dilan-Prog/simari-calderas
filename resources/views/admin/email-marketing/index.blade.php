@extends('admin.layouts.master')

@section('title', 'Email Marketing - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/email-marketing.css', 'resources/css/admin/pages/email-marketing-builder.css'])
@endpush

@section('content')
<div id="email-marketing-root"
     data-templates-url="{{ route('admin.email-templates.index') }}"
     data-lists-url="{{ route('admin.email-lists.index') }}"
     data-campaigns-url="{{ route('admin.email-campaigns.index') }}"
     data-sequences-url="{{ route('admin.email-sequences.index') }}"
     data-user-initials="{{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1)) }}">
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/email-marketing.jsx'])
@endpush
