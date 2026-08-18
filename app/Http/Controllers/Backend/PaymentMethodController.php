<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

/**
 * CRUD del catálogo "Métodos de Pago" (admin). Catálogo chico — sin
 * paginación pesada ni edición en lote, mismo estilo de respuestas JSON
 * que BrandController (sin su parte de bulk-edit, que no aplica aquí).
 */
class PaymentMethodController extends Controller
{
    // Únicos tipos soportados hoy. `type` es string libre en BD (no enum)
    // para poder agregar tipos nuevos sin migración — esta lista es la
    // única fuente de verdad de validación.
    private const TYPES = ['transferencia', 'pasarela', 'referencia', 'credito', 'digital', 'otro'];

    public function index()
    {
        $paymentMethods = PaymentMethod::orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.payment-methods.index', compact('paymentMethods'));
    }

    /**
     * Regla de negocio para type=transferencia: se necesita al menos una
     * forma de que el cliente pueda identificar la cuenta destino, ya sea
     * CLABE (banco + clabe) o número de cuenta. No se exige todo junto
     * porque algunos bancos/casos solo publican uno u otro. Los demás
     * tipos no tienen campos bancarios obligatorios — esos datos no
     * aplican y quedan null.
     *
     * `details` se arma aparte por `buildDetailsForType()` según el tipo
     * (cada tipo tiene su propio set de campos anidados) y se agrega al
     * array devuelto bajo la clave 'details', reemplazando siempre por
     * completo cualquier `details` previo cuando el tipo cambia.
     */
    private function validatePaymentMethod(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'type' => 'required|string|in:' . implode(',', self::TYPES),
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:140',
            'logo_url' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'bank_name' => 'nullable|string|max:100',
            'clabe' => 'nullable|string|max:18',
            'account_number' => 'nullable|string|max:30',
            'beneficiary_name' => 'nullable|string|max:150',
            'sort_order' => 'nullable|integer|min:0',
            'channels' => 'nullable|array',
            'channels.*' => 'in:online,cotizaciones,mostrador',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gte:min_amount',
            'allows_invoicing' => 'nullable|boolean',
        ]);

        if ($data['type'] === 'transferencia') {
            $hasClabe = filled($request->input('bank_name')) && filled($request->input('clabe'));
            $hasAccountNumber = filled($request->input('account_number'));

            if (!$hasClabe && !$hasAccountNumber) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'clabe' => 'Para transferencia, captura Banco + CLABE o al menos el número de cuenta.',
                ]);
            }
        }

        $data['details'] = $this->buildDetailsForType($request, $data['type']);

        return $data;
    }

    /**
     * Valida y arma el `details` (JSON) propio de cada tipo. `otro` no
     * tiene campos y siempre queda en null. Cada rama valida solo sus
     * propios campos anidados `details.*` — un campo requerido/validado
     * para un tipo nunca se exige para otro.
     */
    private function buildDetailsForType(Request $request, string $type): ?array
    {
        return match ($type) {
            'transferencia' => $this->validateTransferenciaDetails($request),
            'pasarela', 'digital' => $this->validatePasarelaDigitalDetails($request),
            'referencia' => $this->validateReferenciaDetails($request),
            'credito' => $this->validateCreditoDetails($request),
            'otro' => null,
        };
    }

    private function validateTransferenciaDetails(Request $request): array
    {
        $data = $request->validate([
            'details.moneda' => 'nullable|in:MXN,USD,EUR',
            'details.swift_aba' => 'nullable|string|max:34',
        ]);
        $d = $data['details'] ?? [];

        return [
            'moneda' => $d['moneda'] ?? 'MXN',
            'swift_aba' => $d['swift_aba'] ?? null,
        ];
    }

    private function validatePasarelaDigitalDetails(Request $request): array
    {
        $data = $request->validate([
            'details.processor' => 'nullable|in:stripe,conekta,mercadopago,openpay,paypal,clip',
            'details.comision_percent' => 'nullable|numeric|min:0|max:100',
            'details.comision_fija' => 'nullable|numeric|min:0',
            'details.llave_publica' => 'nullable|string|max:255',
            'details.msi' => 'nullable|array',
            'details.msi.*' => 'in:3,6,9,12,18',
            'details.captura_manual' => 'nullable|boolean',
        ]);
        $d = $data['details'] ?? [];

        return [
            'processor' => $d['processor'] ?? null,
            'comision_percent' => $d['comision_percent'] ?? null,
            'comision_fija' => $d['comision_fija'] ?? null,
            'llave_publica' => $d['llave_publica'] ?? null,
            'msi' => $d['msi'] ?? null,
            'captura_manual' => $d['captura_manual'] ?? null,
        ];
    }

    private function validateReferenciaDetails(Request $request): array
    {
        $data = $request->validate([
            'details.convenio' => 'nullable|string|max:150',
            'details.vigencia' => 'nullable|in:24h,48h,72h,7dias',
            'details.instrucciones' => 'nullable|string|max:500',
        ]);
        $d = $data['details'] ?? [];

        return [
            'convenio' => $d['convenio'] ?? null,
            'vigencia' => $d['vigencia'] ?? null,
            'instrucciones' => $d['instrucciones'] ?? null,
        ];
    }

    private function validateCreditoDetails(Request $request): array
    {
        $data = $request->validate([
            'details.plazo_credito' => 'nullable|in:15,30,45,60',
            'details.linea_credito' => 'nullable|numeric|min:0',
            'details.requiere_aprobacion' => 'nullable|boolean',
        ]);
        $d = $data['details'] ?? [];

        return [
            'plazo_credito' => $d['plazo_credito'] ?? null,
            'linea_credito' => $d['linea_credito'] ?? null,
            'requiere_aprobacion' => $d['requiere_aprobacion'] ?? null,
        ];
    }

    public function store(Request $request)
    {
        $data = $this->validatePaymentMethod($request);

        $paymentMethod = new PaymentMethod();
        $paymentMethod->type = $data['type'];
        $paymentMethod->name = $data['name'];
        $paymentMethod->description = $data['description'] ?? null;
        $paymentMethod->logo_url = $data['logo_url'] ?? null;
        $paymentMethod->is_active = $request->boolean('is_active', true);
        $paymentMethod->bank_name = $data['bank_name'] ?? null;
        $paymentMethod->clabe = $data['clabe'] ?? null;
        $paymentMethod->account_number = $data['account_number'] ?? null;
        $paymentMethod->beneficiary_name = $data['beneficiary_name'] ?? null;
        $paymentMethod->details = $data['details'];
        $paymentMethod->channels = $data['channels'] ?? null;
        $paymentMethod->min_amount = $data['min_amount'] ?? null;
        $paymentMethod->max_amount = $data['max_amount'] ?? null;
        $paymentMethod->allows_invoicing = $request->boolean('allows_invoicing');
        $paymentMethod->sort_order = $data['sort_order'] ?? 0;
        $paymentMethod->save();

        return response()->json([
            'success' => true,
            'paymentMethod' => $paymentMethod,
        ]);
    }

    public function edit($id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);

        return response()->json($paymentMethod);
    }

    public function update(Request $request, $id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);

        $data = $this->validatePaymentMethod($request, (int) $id);

        $paymentMethod->type = $data['type'];
        $paymentMethod->name = $data['name'];
        $paymentMethod->description = $data['description'] ?? null;
        $paymentMethod->logo_url = $data['logo_url'] ?? null;
        $paymentMethod->is_active = $request->boolean('is_active', true);
        $paymentMethod->bank_name = $data['bank_name'] ?? null;
        $paymentMethod->clabe = $data['clabe'] ?? null;
        $paymentMethod->account_number = $data['account_number'] ?? null;
        $paymentMethod->beneficiary_name = $data['beneficiary_name'] ?? null;
        $paymentMethod->details = $data['details'];
        $paymentMethod->channels = $data['channels'] ?? null;
        $paymentMethod->min_amount = $data['min_amount'] ?? null;
        $paymentMethod->max_amount = $data['max_amount'] ?? null;
        $paymentMethod->allows_invoicing = $request->boolean('allows_invoicing');
        $paymentMethod->sort_order = $data['sort_order'] ?? 0;
        $paymentMethod->save();

        return response()->json([
            'success' => true,
            'paymentMethod' => $paymentMethod,
        ]);
    }

    public function destroy($id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);
        $paymentMethod->delete();

        return response()->json(['success' => true]);
    }
}
