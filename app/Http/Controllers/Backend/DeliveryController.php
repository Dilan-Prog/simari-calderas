<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Delivery;

class DeliveryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index()
{
   $deliveries = Delivery::get([
        'id', 
        'name', 
        'code', 
        'tracking_url_template', 
        'phone', 
        'website', 
        'is_active', 
        'created_at'
    ]);

    return view('admin.delivery.index', compact('deliveries'));
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
        $request->validate([
        'name'                  => 'required|string|max:120|unique:carriers,name',
        'code'                  => 'required|string|max:50|unique:carriers,code',
        'tracking_url_template' => 'nullable|string|max:255',
        'phone'                 => 'nullable|string|max:30',
        'website'               => 'nullable|string|max:255',
    ]);

    $delivery = new Delivery;
    $delivery->name = $request->name;
    $delivery->code = $request->code;
    $delivery->tracking_url_template = $request->tracking_url_template;
    $delivery->phone = $request->phone;
    $delivery->website = $request->website;
    $delivery->is_active = $request->is_active;
    $delivery->save();
    return redirect()->route('admin.deliveries.index')
                     ->with('success', 'Paquetería creada correctamente.');
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
    
    $delivery = Delivery::findOrFail($id);

    
    $request->validate([
        'name'                  => 'required|string|max:120|unique:carriers,name,' . $id,
        'code'                  => 'required|string|max:50|unique:carriers,code,' . $id,
        'tracking_url_template' => 'nullable|string|max:255',
        'phone'                 => 'nullable|string|max:30',
        'website'               => 'nullable|string|max:255',
        'is_active'             => 'required|in:1,0',
    ]);

    
    $delivery->name = $request->name;
    $delivery->code = $request->code;
    $delivery->tracking_url_template = $request->tracking_url_template;
    $delivery->phone = $request->phone;
    $delivery->website = $request->website;
    $delivery->is_active = $request->is_active;
    
    
    $delivery->save();


    return redirect()->route('admin.deliveries.index')
                     ->with('success', 'Paquetería actualizada correctamente.');    
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->delete();

        return redirect()->route('admin.deliveries.index')->with('success', 'Paquetería eliminada correctamente.');
    }
}
