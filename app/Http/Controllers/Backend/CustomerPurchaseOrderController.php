<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CustomerPurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff: consulta/descarga del PDF de las órdenes de compra que los clientes suben en el portal.
 * La enlaza la ficha del cliente; la ruta va protegida con permission:clients,view.
 */
class CustomerPurchaseOrderController extends Controller
{
    public function download(Request $request, int $id): Response
    {
        $order = CustomerPurchaseOrder::findOrFail($id);
        $disk  = Storage::disk(CustomerPurchaseOrder::DISK);

        abort_unless($disk->exists($order->file_path), 404);

        return $disk->response(
            $order->file_path,
            $order->original_name,
            ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff'],
            $request->boolean('download') ? 'attachment' : 'inline'
        );
    }
}
