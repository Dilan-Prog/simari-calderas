<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

/**
 * Panel "Productos" del grupo Ventas: tabla de datos personalizada para el
 * equipo comercial. Independiente del CRUD de catálogo (admin.products.*,
 * grupo Ecommerce) -- aquí solo se consultan productos para vender.
 */
class SalesProductController extends Controller
{
    public function index()
    {
        return view('admin.sales-products.index');
    }
}
