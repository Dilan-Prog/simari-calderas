@extends('admin.layouts.master')

@section('title')
    Paqueterias - Admin
@endsection

@section('content')
PUTO JAIME


    @include('admin.delivery.partials._modal_create')
    @include('admin.delivery.partials._edit_modal')
    @include('admin.delivery.partials._modal_delete')
    @include('admin.delivery.partials._scripts')
@endsection
