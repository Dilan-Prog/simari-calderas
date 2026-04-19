<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return view('admin.products.index');
    }
    public function create()
    {
        return view('admin.products.create');
    }
    public function store(Request $request)
    {
        // Validar y guardar el producto
    }
    public function edit($id)
    {
        
    }
}
