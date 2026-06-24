<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index()
{
    $paqueterias = [];
    
    // Lista de nombres reales para que el jefe lo vea más profesional
    $nombresBase = ['FedEx', 'DHL', 'Estafeta', 'UPS', 'MercadoLibre', 'Redpack', 'Castores', 'Tresguerras'];

    // Ciclo que da 30 vueltas para crear 30 registros
    for ($i = 1; $i <= 30; $i++) {
        
       
        $nombreAleatorio = $nombresBase[array_rand($nombresBase)];

        $paqueterias[] = (object) [
            'id' => $i,
            
            'nombre' => $nombreAleatorio . ' #' . $i,
            
            'tiempo_entrega' => rand(1, 3) . '-' . rand(4, 7) . ' días',
           
            'cobertura' => ($i % 3 == 0) ? 'Local' : 'Nacional',
            
            'estado' => ($i % 4 == 0) ? 'Inactiva' : 'Activa'
        ];
    }

    return view('admin.delivery.index', compact('paqueterias'));
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
