 @extends('frontend.layouts.master')
 @section('styles')
        <!-- (Opcional) Estilos específicos para esta página -->
        <link rel="stylesheet" href="{{ asset('css/home.css') }}" />
 @endsection
 @section('content')
    
 @endsection
    <script>
      document.getElementById("year").textContent = new Date().getFullYear();
    </script>