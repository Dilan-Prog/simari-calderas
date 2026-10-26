<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\CustomerPurchaseOrder;
use App\Models\Quote;
use App\Support\PortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Órdenes de Compra" del portal: el cliente sube el PDF de SU orden de compra hacia Equiterm
 * (no confundir con el módulo admin `purchase-orders`, que son las compras de Equiterm a proveedores).
 *
 * Los datos pertenecen a la CUENTA (auth('customer')->id()), no al usuario del equipo. El parámetro
 * {purchaseOrder} llega sin tipo (sin binding implícito) y se resuelve siempre acotado al cliente
 * autenticado: la OC de otro cliente responde 404. El PDF vive en el disco privado `local`.
 */
class PurchaseOrderController extends Controller
{
    public const MAX_KB = 10240; // 10 MB

    private const PER_PAGE = 15;

    public function index(Request $request)
    {
        $customerId = auth('customer')->id();

        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = trim((string) $request->query('q', ''));

        $orders = CustomerPurchaseOrder::query()
            ->where('customer_id', $customerId)
            ->with('quote:id,quote_number,currency,total')
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . addcslashes($search, '\\%_') . '%';
                $q->where(fn ($w) => $w->where('po_number', 'like', $like)->orWhere('original_name', 'like', $like));
            })
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Nombre de quien subió cada archivo (usuarios del equipo de ESTA cuenta; null = titular).
        $memberIds = $orders->pluck('uploaded_by_member_id')->filter()->unique()->all();
        $members   = $memberIds
            ? \App\Models\CustomerPortalUser::where('customer_id', $customerId)->whereIn('id', $memberIds)->get()->mapWithKeys(fn ($m) => [$m->id => $m->full_name])->all()
            : [];

        return view('customers.purchase-orders.index', [
            'orders'    => $orders,
            'members'   => $members,
            'search'    => $search,
            'quotes'    => $this->linkableQuotes($customerId),
            'canCreate' => PortalContext::can('purchase-orders', 'create'),
            'canDelete' => PortalContext::can('purchase-orders', 'delete'),
            'maxMb'     => self::MAX_KB / 1024,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customerId = auth('customer')->id();

        $validated = $request->validate([
            'file' => [
                'required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:' . self::MAX_KB,
                // Además de extensión y MIME, el contenido debe empezar con la firma de un PDF.
                function (string $attribute, $value, \Closure $fail) {
                    $handle = is_object($value) && $value->getRealPath() ? @fopen($value->getRealPath(), 'rb') : false;
                    $head   = $handle ? fread($handle, 5) : '';
                    if ($handle) {
                        fclose($handle);
                    }
                    if ($head !== '%PDF-') {
                        $fail('El archivo no es un PDF válido.');
                    }
                },
            ],
            'po_number' => ['nullable', 'string', 'max:60'],
            'po_date'   => ['nullable', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:' . now()->addDay()->toDateString()],
            'quote_id'  => [
                'nullable', 'integer',
                Rule::exists('quotes', 'id')
                    ->where('customer_id', $customerId)
                    ->whereIn('status', ['sent', 'accepted']),
            ],
            'notes'     => ['nullable', 'string', 'max:1000'],
        ], [
            'file.required'           => 'Selecciona el archivo PDF de tu orden de compra.',
            'file.file'               => 'No se pudo recibir el archivo; inténtalo de nuevo.',
            'file.uploaded'           => 'No se pudo subir el archivo (revisa que no pese más de ' . (self::MAX_KB / 1024) . ' MB).',
            'file.mimes'              => 'El archivo debe ser un PDF.',
            'file.mimetypes'          => 'El archivo debe ser un PDF.',
            'file.max'                => 'El PDF no puede pesar más de ' . (self::MAX_KB / 1024) . ' MB.',
            'po_number.max'           => 'El número de orden de compra no puede exceder 60 caracteres.',
            'po_date.date'            => 'La fecha de la orden de compra no es válida.',
            'po_date.after_or_equal'  => 'La fecha de la orden de compra no es válida.',
            'po_date.before_or_equal' => 'La fecha de la orden de compra no puede ser futura.',
            'quote_id.exists'         => 'La cotización elegida no está disponible.',
            'notes.max'               => 'Las notas no pueden exceder 1000 caracteres.',
        ]);

        $file = $request->file('file');
        $path = $file->storeAs(
            'customer-purchase-orders/' . $customerId,
            Str::random(40) . '.pdf',
            CustomerPurchaseOrder::DISK
        );

        if (! $path) {
            return back()->withInput()->withErrors(['file' => 'No se pudo guardar el archivo; inténtalo de nuevo.']);
        }

        $order = CustomerPurchaseOrder::create([
            'customer_id'           => $customerId,
            'po_number'             => $this->clean($validated['po_number'] ?? null),
            'po_date'               => $validated['po_date'] ?? null,
            'quote_id'              => $validated['quote_id'] ?? null,
            'notes'                 => $this->clean($validated['notes'] ?? null),
            'file_path'             => $path,
            'original_name'         => $this->displayName($file->getClientOriginalName()),
            'mime'                  => 'application/pdf',
            'size'                  => (int) $file->getSize(),
            'uploaded_by_member_id' => PortalContext::memberId(),
        ]);

        // Aviso al personal (tarea + correo a administración); un fallo nunca afecta la subida del cliente.
        try {
            app(\App\Services\Portal\PortalStaffAlerts::class)->purchaseOrderUploaded($order);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()
            ->route('customer.purchase-orders.index')
            ->with('success', 'Recibimos tu orden de compra; nuestro equipo la revisará.');
    }

    /** Muestra el PDF en el navegador (con el nombre original) tras verificar que es del cliente. */
    public function download(Request $request, $purchaseOrder): Response
    {
        $order = $this->findOwned($purchaseOrder);
        $disk  = Storage::disk(CustomerPurchaseOrder::DISK);

        abort_unless($disk->exists($order->file_path), 404);

        return $disk->response(
            $order->file_path,
            $order->original_name,
            ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff'],
            $request->boolean('download') ? 'attachment' : 'inline'
        );
    }

    public function destroy($purchaseOrder): RedirectResponse
    {
        $order = $this->findOwned($purchaseOrder);
        $order->delete(); // el evento `deleted` del modelo borra también el PDF

        return redirect()
            ->route('customer.purchase-orders.index')
            ->with('success', 'Se eliminó la orden de compra.');
    }

    private function findOwned($id): CustomerPurchaseOrder
    {
        abort_unless(ctype_digit((string) $id), 404);

        return CustomerPurchaseOrder::where('customer_id', auth('customer')->id())->findOrFail((int) $id);
    }

    /** Cotizaciones del cliente que puede ligar a su OC (enviadas o aceptadas; nunca borradores). */
    private function linkableQuotes(int $customerId)
    {
        return Quote::query()
            ->where('customer_id', $customerId)
            ->whereIn('status', ['sent', 'accepted'])
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'quote_number', 'currency', 'total']);
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** Nombre original seguro para mostrar y para Content-Disposition (sin rutas ni caracteres de control). */
    private function displayName(?string $name): string
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = preg_replace('/[\x00-\x1F\x7F<>:"|?*]+/u', '', $name) ?? '';
        $base = trim(preg_replace('/\.pdf$/i', '', $name));
        $base = Str::limit($base !== '' ? $base : 'orden-de-compra', 120, '');

        return $base . '.pdf';
    }
}
