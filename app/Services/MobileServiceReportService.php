<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ServiceReport;
use App\Models\ServiceReportImage;
use App\Models\ServiceReportType;
use App\Models\TechnicalService;
use App\Models\User;
use App\Support\UploadPath;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Alta de reportes de servicio desde la app móvil (POST /api/mobile/v1/reportes).
 *
 * La app captura el reporte completo y firmado en un asistente de 5 pasos y lo
 * manda de una sola vez (multipart, desde su bandeja de salida). Aquí se traduce
 * al modelo del panel reutilizando ServiceReportService (folio, actividad,
 * imágenes) para que el reporte quede igual que uno hecho en el asistente web.
 *
 * Decisiones (documentadas también en el controlador):
 *  - Técnico: assigned_user_id y created_by_user_id = usuario del token. El
 *    `tecnico_id` que manda la app se acepta pero se ignora (la app siempre manda
 *    el propio; aceptar otro permitiría crear reportes a nombre de terceros).
 *  - Firma: la app manda el trazo SVG (`firma[trazo]`, atributo `d` de un path)
 *    y además el archivo `firma` (.svg). Se rasteriza a PNG con GD y se guarda
 *    como data URL `data:image/png;base64,...` en signature_data — el mismo
 *    formato que el panel —, porque es lo único que pintan igual DomPDF (PDF del
 *    reporte) y el <Image> de React Native (detalle en la app), que no dibuja SVG.
 */
class MobileServiceReportService
{
    /** Tipo de servicio de la app => key de service_report_types. */
    public const TYPE_MAP = [
        'preventivo'               => 'maintenance_preventive',
        'mantenimiento preventivo' => 'maintenance_preventive',
        'correctivo'               => 'maintenance_corrective',
        'mantenimiento correctivo' => 'maintenance_corrective',
        'inspeccion'               => 'inspection',
        'arranque'                 => 'startup',
        'puesta en marcha'         => 'startup',
    ];

    public const MAX_PHOTOS = 20;

    private const EQUIPMENT_KEYS = ['tipo', 'marca', 'modelo', 'numero_serie', 'capacidad', 'ubicacion'];

    private const READING_KEYS = ['presion_operacion', 'temperatura_chimenea', 'tds', 'ph'];

    public function __construct(private ServiceReportService $reports) {}

    // ── Idempotencia ──────────────────────────────────────────────────────────

    /** Key del header Idempotency-Key (o del campo idempotency_key). 422 si falta o es inválida. */
    public function idempotencyKey(Request $request): string
    {
        $header = trim((string) $request->header('Idempotency-Key', ''));
        $body   = $request->input('idempotency_key');
        $body   = is_scalar($body) ? trim((string) $body) : '';

        if ($header !== '' && $body !== '' && $header !== $body) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'El header Idempotency-Key y el campo idempotency_key no coinciden.',
            ]);
        }

        $key = $header !== '' ? $header : $body;

        if ($key === '') {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Falta la clave de idempotencia (header Idempotency-Key).',
            ]);
        }

        if (strlen($key) > 100 || ! preg_match('/^[A-Za-z0-9._:\-]+$/', $key)) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'La clave de idempotencia debe tener máximo 100 caracteres (letras, números, «.», «_», «:» o «-»).',
            ]);
        }

        return $key;
    }

    /** Reporte ya creado con esa key (reintento de la bandeja de salida), o null. */
    public function findByKey(string $key, User $user): ?ServiceReport
    {
        $report = ServiceReport::where('idempotency_key', $key)->first();

        if ($report && (int) $report->created_by_user_id !== (int) $user->id) {
            // Una key es de un solo dispositivo/usuario: no se revela ni se reusa el reporte ajeno.
            throw ValidationException::withMessages([
                'idempotency_key' => 'Esta clave de idempotencia ya fue usada por otro usuario.',
            ]);
        }

        return $report;
    }

    // ── Validación ────────────────────────────────────────────────────────────

    /**
     * Valida el cuerpo. Ojo: `firma` llega a la vez como arreglo de texto
     * (firma[nombre], firma[trazo]…) y como ARCHIVO `firma`; $request->all()
     * mezcla ambos y el archivo pisa el arreglo, así que se valida input() y los
     * archivos por separado (el archivo de firma como `firma_archivo`).
     */
    public function validate(Request $request): array
    {
        $scalar = function (string $attribute, $value, \Closure $fail) {
            if ($value !== null && ! is_scalar($value)) {
                $fail('El campo :attribute debe ser un número o texto.');
            }
        };

        $data = $request->input();
        unset($data['fotos'], $data['firma_archivo']);
        $data['fotos']         = $request->file('fotos');
        $data['firma_archivo'] = $request->file('firma');
        if (! is_array($data['firma'] ?? null)) {
            unset($data['firma']);
        }

        $rules = [
            'origen'                => ['nullable', 'string', 'max:20'],
            'capturado_en'          => ['nullable', 'date'],
            'tecnico_id'            => ['nullable', 'integer'],
            'servicio_id'           => [
                'nullable', 'integer',
                Rule::exists('services', 'id')->whereIn('status', ['scheduled', 'in_progress', 'completed']),
            ],
            'cliente_id'            => ['nullable', 'integer', 'exists:customers,id'],
            'cliente'               => ['nullable', 'array'],
            'cliente.id'            => ['nullable', 'integer', 'exists:customers,id'],
            'cliente.nombre'        => ['nullable', 'string', 'max:200'],
            'cliente.empresa'       => ['nullable', 'string', 'max:200'],
            'cliente.ubicacion'     => ['nullable', 'string', 'max:200'],
            'equipo'                => ['nullable', 'array'],
            'equipo.tipo'           => ['nullable', 'string', 'max:100'],
            'equipo.marca'          => ['nullable', 'string', 'max:100'],
            'equipo.modelo'         => ['nullable', 'string', 'max:100'],
            'equipo.numero_serie'   => ['nullable', 'string', 'max:100'],
            'equipo.capacidad'      => ['nullable', 'string', 'max:100'],
            'equipo.ubicacion'      => ['nullable', 'string', 'max:200'],
            'tipo_servicio'         => ['required', 'string', 'max:100'],
            'hallazgos'             => ['nullable', 'string', 'max:10000'],
            'trabajos_realizados'   => ['required', 'string', 'max:10000'],
            'recomendaciones'       => ['nullable', 'string', 'max:10000'],
            'materiales'            => ['nullable', 'array', 'max:100'],
            'materiales.*'          => ['array'],
            'materiales.*.descripcion' => ['nullable', 'string', 'max:255'],
            'materiales.*.cantidad' => ['nullable', $scalar, 'max:30'],
            'materiales.*.unidad'   => ['nullable', 'string', 'max:30'],
            'lecturas'              => ['nullable', 'array'],
            'fotos'                 => ['nullable', 'array', 'max:' . self::MAX_PHOTOS],
            'fotos.*'               => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'fotos_meta'            => ['nullable', 'array', 'max:' . self::MAX_PHOTOS],
            'fotos_meta.*'          => ['array'],
            'fotos_meta.*.orden'    => ['nullable', 'integer', 'min:0'],
            'fotos_meta.*.descripcion' => ['nullable', 'string', 'max:255'],
            'firma'                 => ['nullable', 'array'],
            'firma.nombre'          => ['nullable', 'string', 'max:150'],
            'firma.trazo'           => ['nullable', 'string', 'max:500000'],
            'firma.ancho'           => ['nullable', 'numeric', 'min:1', 'max:5000'],
            'firma.alto'            => ['nullable', 'numeric', 'min:1', 'max:5000'],
            'firma.firmado_en'      => ['nullable', 'date'],
            'firma_archivo'         => ['nullable', 'file', 'max:1024'],
        ];
        foreach (self::READING_KEYS as $k) {
            $rules["lecturas.{$k}"] = ['nullable', $scalar, 'max:50'];
        }

        $attributes = [
            'tipo_servicio'       => 'tipo de servicio',
            'trabajos_realizados' => 'trabajos realizados',
            'servicio_id'         => 'servicio',
            'cliente_id'          => 'cliente',
            'cliente.id'          => 'cliente',
            'cliente.nombre'      => 'nombre del cliente',
            'fotos.*'             => 'foto',
            'firma.nombre'        => 'nombre de quien firma',
            'firma_archivo'       => 'archivo de firma',
        ];

        $validated = Validator::make($data, $rules, [], $attributes)->validate();

        $clienteId = $validated['cliente_id'] ?? null;
        $nestedId  = $validated['cliente']['id'] ?? null;
        if ($clienteId && $nestedId && (int) $clienteId !== (int) $nestedId) {
            throw ValidationException::withMessages(['cliente_id' => 'cliente_id y cliente[id] no coinciden.']);
        }

        $validated['_service_type'] = $this->resolveServiceType($validated['tipo_servicio']);

        if ($this->clean($validated['trabajos_realizados'] ?? null, true) === null) {
            throw ValidationException::withMessages(['trabajos_realizados' => 'Describe los trabajos realizados.']);
        }

        return $validated;
    }

    /** «Preventivo» / «Arranque» / key activa… => key de service_report_types (solo tipos de actividades). */
    public function resolveServiceType(string $value): string
    {
        $norm  = $this->normalize($value);
        $types = ServiceReportType::active()->get(['key', 'label', 'form_behavior']);

        $key = self::TYPE_MAP[$norm] ?? null;
        $type = $key
            ? $types->firstWhere('key', $key)
            : ($types->firstWhere('key', trim($value)) ?? $types->first(fn ($t) => $this->normalize($t->label) === $norm));

        // La app captura «trabajos realizados» (actividad): un tipo de
        // mediciones o personalizado quedaría con el contenido invisible.
        if (! $type || $type->form_behavior !== 'activity') {
            throw ValidationException::withMessages([
                'tipo_servicio' => 'El tipo de servicio no es válido. Usa Preventivo, Correctivo, Arranque o Inspección.',
            ]);
        }

        return $type->key;
    }

    // ── Alta ──────────────────────────────────────────────────────────────────

    /**
     * Crea el reporte en una transacción. Devuelve [reporte, warnings, replayed].
     * replayed = true si otra petición con la misma key ganó la carrera.
     *
     * @return array{0: ServiceReport, 1: string[], 2: bool}
     */
    public function create(array $v, Request $request, User $user, string $key): array
    {
        $warnings = [];
        $attrs    = $this->buildAttributes($v, $request, $user, $key, $warnings);
        $activity = [
            'content'         => $this->clean($v['trabajos_realizados'], true, 10000),
            'systems_checked' => str_contains($this->normalize($attrs['equipment']['tipo'] ?? ''), 'caldera') ? ['calderas'] : [],
        ];
        $photos = $this->pairPhotos($request->file('fotos') ?? [], $v['fotos_meta'] ?? []);

        $lastError = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $photoWarnings = [];
            try {
                $report = DB::transaction(function () use ($attrs, $activity, $photos, &$photoWarnings) {
                    return $this->persist($attrs, $activity, $photos, $photoWarnings);
                });

                return [$report, array_merge($warnings, $photoWarnings), false];
            } catch (QueryException $e) {
                if (! $this->isUniqueViolation($e)) {
                    throw $e;
                }
                // Otra petición con la misma key se guardó primero: es un reintento.
                if ($existing = ServiceReport::where('idempotency_key', $key)->first()) {
                    return [$existing, [], true];
                }
                // Si no, chocó el folio (generateReportNumber no es atómico): se reintenta.
                $lastError = $e;
            }
        }

        throw $lastError;
    }

    private function persist(array $attrs, array $activity, array $photos, array &$warnings): ServiceReport
    {
        $report = ServiceReport::create($attrs + ['report_number' => $this->reports->generateReportNumber()]);

        $this->reports->saveActivity($report, $activity);

        $stored = [];
        try {
            foreach ($photos as $i => [$file, $caption]) {
                // Una por una para saber cuál falló y conservar su pie de foto.
                if ($this->reports->saveImages($report, [$file]) > 0) {
                    $warnings[] = sprintf(
                        'No se pudo procesar la foto %d (formato no compatible o archivo dañado); el reporte se guardó sin ella.',
                        $i + 1
                    );
                    continue;
                }
                $image = ServiceReportImage::where('service_report_id', $report->id)->orderByDesc('id')->first();
                $stored[] = $image->path;
                if ($caption !== null) {
                    $image->update(['caption' => $caption]);
                }
            }
        } catch (\Throwable $e) {
            // La transacción se revierte: no dejar archivos huérfanos en disco
            // (solo los de este reporte; un duplicado perceptual apunta a otro).
            foreach ($stored as $path) {
                if (str_starts_with($path, "service-reports/{$report->id}/")) {
                    @unlink(UploadPath::full($path));
                }
            }
            throw $e;
        }

        return $report;
    }

    private function buildAttributes(array $v, Request $request, User $user, string $key, array &$warnings): array
    {
        $tz         = config('app.timezone');
        $capturedAt = ! empty($v['capturado_en']) ? Carbon::parse($v['capturado_en'])->setTimezone($tz) : now();

        $equipment = $this->cleanAssoc($v['equipo'] ?? [], self::EQUIPMENT_KEYS, 200);
        $cliente   = $v['cliente'] ?? [];

        // ── Cliente / servicio ───────────────────────────────────────────────
        $serviceId     = $v['servicio_id'] ?? null;
        $servicePageId = null;
        $customerId    = $v['cliente_id'] ?? ($cliente['id'] ?? null);

        if ($serviceId) {
            // Igual que el panel: el cliente se deriva del servicio, nunca del request.
            $technical     = TechnicalService::findOrFail($serviceId);
            $servicePageId = $technical->service_page_id;
            if ($technical->customer_id) {
                if ($customerId && (int) $customerId !== (int) $technical->customer_id) {
                    $warnings[] = 'El cliente se tomó del servicio vinculado (no coincidía con el elegido en la app).';
                }
                $customerId = $technical->customer_id;
            }
        }

        $customer = $customerId ? Customer::find($customerId) : null;
        if ($customer) {
            $name = $this->clean(trim("{$customer->first_name} {$customer->last_name}"), false, 200);
            $customerName    = $name ?? $this->clean($customer->company, false, 200) ?? $this->clean($cliente['nombre'] ?? null, false, 200);
            $customerCompany = $this->clean($customer->company, false, 200);
            $customerRfc     = $this->clean($customer->rfc, false, 20);
            $customerPhone   = $this->clean($customer->phone, false, 30);
        } else {
            $customerName    = $this->clean($cliente['nombre'] ?? null, false, 200);
            $customerCompany = $this->clean($cliente['empresa'] ?? null, false, 200);
            $customerRfc     = null;
            $customerPhone   = null;
        }

        if ($customerName === null) {
            throw ValidationException::withMessages(['cliente.nombre' => 'Escribe el nombre del cliente o elige uno de la lista.']);
        }

        $location = $this->clean($cliente['ubicacion'] ?? null, false, 200) ?? ($equipment['ubicacion'] ?? null);

        // ── Firma ────────────────────────────────────────────────────────────
        $firma     = $v['firma'] ?? [];
        $signature = $this->signatureDataUrl($firma, $request->file('firma'), $warnings);
        $signer    = $this->clean($firma['nombre'] ?? null, false, 150);
        if ($signature !== null && $signer === null) {
            throw ValidationException::withMessages(['firma.nombre' => 'Escribe el nombre de quien firma.']);
        }
        $signedAt = ! empty($firma['firmado_en']) ? Carbon::parse($firma['firmado_en'])->setTimezone($tz) : $capturedAt;

        return [
            'created_by_user_id'  => $user->id,
            'assigned_user_id'    => $user->id,
            'service_id'          => $serviceId,
            'service_page_id'     => $servicePageId,
            'customer_id'         => $customer?->id,
            'customer_name'       => $customerName,
            'customer_company'    => $customerCompany,
            'customer_rfc'        => $customerRfc,
            'customer_phone'      => $customerPhone,
            'service_type'        => $v['_service_type'],
            'custom_service_type' => null,
            'service_date'        => $capturedAt->toDateString(),
            'week_number'         => $capturedAt->isoWeek(),
            'location'            => $location,
            'observations'        => $this->clean($v['hallazgos'] ?? null, true, 10000),
            'recommendations'     => $this->clean($v['recomendaciones'] ?? null, true, 10000),
            'include_sampling'    => false,
            'equipment'           => $equipment ?: null,
            'readings'            => $this->cleanReadings($v['lecturas'] ?? []),
            'materials'           => $this->cleanMaterials($v['materiales'] ?? []),
            'origin'              => 'app_movil',
            'captured_at'         => $capturedAt,
            'idempotency_key'     => $key,
            'signature_data'      => $signature,
            'signature_name'      => $signature ? $signer : null,
            'signature_position'  => $signature ? 'Cliente' : null,
            'signature_phone'     => null,
            'signed_at'           => $signature ? $signedAt : null,
            'status'              => $signature ? 'signed' : 'completed',
            'current_step'        => $signature ? 6 : 5,
        ];
    }

    /**
     * Empareja fotos[i] con fotos_meta[i] (la app los manda en el mismo orden;
     * si trae `orden`, se usa para ordenar).
     *
     * @return array<int, array{0: UploadedFile, 1: ?string}>
     */
    private function pairPhotos(array $files, array $meta): array
    {
        $pairs = [];
        foreach (array_values($files) as $i => $file) {
            $m       = $meta[$i] ?? [];
            $pairs[] = [
                'file'    => $file,
                'caption' => $this->clean($m['descripcion'] ?? null, false, 255),
                'orden'   => isset($m['orden']) ? (int) $m['orden'] : $i + 1,
                'i'       => $i,
            ];
        }
        usort($pairs, fn ($a, $b) => [$a['orden'], $a['i']] <=> [$b['orden'], $b['i']]);

        return array_map(fn ($p) => [$p['file'], $p['caption']], $pairs);
    }

    // ── Firma ─────────────────────────────────────────────────────────────────

    /**
     * data:image/png;base64 de la firma, o null si no hay firma. Orden:
     * firma[trazo] → archivo `firma` SVG (sus path d) → archivo PNG/JPEG tal cual.
     */
    public function signatureDataUrl(array $firma, ?UploadedFile $file, array &$warnings): ?string
    {
        $trazo = trim((string) ($firma['trazo'] ?? ''));
        $paths = $trazo !== '' ? [$trazo] : [];

        $raster = null;
        if (! $paths && $file && $file->isValid()) {
            $content = (string) @file_get_contents($file->getRealPath());
            if (stripos($content, '<svg') !== false) {
                preg_match_all('/\sd\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $content, $m);
                $paths = array_values(array_filter(array_map(fn ($a, $b) => $a !== '' ? $a : $b, $m[1], $m[2])));
            } elseif (($info = @getimagesize($file->getRealPath())) && in_array($info['mime'], ['image/png', 'image/jpeg'], true)) {
                $raster = 'data:' . $info['mime'] . ';base64,' . base64_encode($content);
            }
        }

        if ($raster) {
            return $raster;
        }

        if (! $paths && ! $file) {
            return null; // el reporte viene sin firma
        }

        $lines = [];
        foreach ($paths as $d) {
            $lines = array_merge($lines, $this->parsePath($d));
        }

        $png = $lines ? $this->rasterize($lines) : null;
        if ($png === null) {
            $warnings[] = 'No se pudo interpretar la firma; el reporte se guardó sin firma.';
        }

        return $png;
    }

    /**
     * Interpreta el atributo `d` de un path SVG como polilíneas (lista de
     * puntos). La app solo usa M/L absolutos; se aceptan también relativos,
     * H/V/Z y, para curvas/arcos, su punto final (aproximación recta).
     *
     * @return array<int, array<int, array{0: float, 1: float}>>
     */
    public function parsePath(string $d): array
    {
        preg_match_all('/[MmLlHhVvCcSsQqTtAaZz]|[-+]?(?:\d+\.?\d*|\.\d+)(?:[eE][-+]?\d+)?/', $d, $m);
        $tokens = $m[0];
        $argc   = ['M' => 2, 'L' => 2, 'H' => 1, 'V' => 1, 'C' => 6, 'S' => 4, 'Q' => 4, 'T' => 2, 'A' => 7];

        $lines = [];
        $cur   = [];
        $cmd   = null;
        $x = $y = $sx = $sy = 0.0;
        $count = 0;
        $n     = count($tokens);
        $i     = 0;

        while ($i < $n && $count < 60000) {
            $t = $tokens[$i];
            if (ctype_alpha($t)) {
                $i++;
                if (strtoupper($t) === 'Z') {
                    if ($cur) {
                        $cur[] = [$sx, $sy];
                    }
                    $x = $sx;
                    $y = $sy;
                    $cmd = null;
                } else {
                    $cmd = $t;
                }
                continue;
            }
            if ($cmd === null) {
                $i++;
                continue;
            }

            $up  = strtoupper($cmd);
            $rel = $cmd !== $up;
            $k   = $argc[$up];
            $args = array_slice($tokens, $i, $k);
            if (count($args) < $k || array_filter($args, 'ctype_alpha')) {
                $i++; // argumentos incompletos: se descarta el token
                continue;
            }
            $i += $k;
            $a = array_map('floatval', $args);

            [$ex, $ey] = match ($up) {
                'H'           => [$a[0], $rel ? 0.0 : $y],
                'V'           => [$rel ? 0.0 : $x, $a[0]],
                'C'           => [$a[4], $a[5]],
                'S', 'Q'      => [$a[2], $a[3]],
                'A'           => [$a[5], $a[6]],
                default       => [$a[0], $a[1]], // M, L, T
            };
            $nx = $rel ? $x + $ex : $ex;
            $ny = $rel ? $y + $ey : $ey;
            if (! is_finite($nx) || ! is_finite($ny)) {
                continue;
            }

            if ($up === 'M') {
                if ($cur) {
                    $lines[] = $cur;
                }
                $cur = [[$nx, $ny]];
                $sx  = $nx;
                $sy  = $ny;
                $cmd = $rel ? 'l' : 'L'; // pares extra tras M son L implícitos
            } else {
                if (! $cur) {
                    $cur = [[$x, $y]];
                }
                $cur[] = [$nx, $ny];
            }
            $x = $nx;
            $y = $ny;
            $count++;
        }
        if ($cur) {
            $lines[] = $cur;
        }

        return $lines;
    }

    /**
     * Dibuja las polilíneas en un PNG transparente recortado a la firma
     * (trazo #141516 de 2.6 unidades, como el SVG de la app), con
     * supersampling 3x para suavizar bordes. Devuelve data URL o null.
     */
    public function rasterize(array $lines): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $xs = $ys = [];
        foreach ($lines as $line) {
            foreach ($line as [$px, $py]) {
                $xs[] = $px;
                $ys[] = $py;
            }
        }
        if (! $xs) {
            return null;
        }

        $stroke = 2.6;
        $pad    = $stroke * 3;
        $minX   = min($xs) - $pad;
        $minY   = min($ys) - $pad;
        $w      = max(1.0, max($xs) + $pad - $minX);
        $h      = max(1.0, max($ys) + $pad - $minY);
        if ($w > 20000 || $h > 20000) {
            return null;
        }

        // Salida máx. 900×360 (el PDF la pinta a 220×64 y la app a ancho de pantalla).
        $scale = min(2.0, 900 / $w, 360 / $h);
        $outW  = max(1, (int) round($w * $scale));
        $outH  = max(1, (int) round($h * $scale));
        $ss    = 3;
        $s     = $scale * $ss;

        $img = imagecreatetruecolor($outW * $ss, $outH * $ss);
        imagealphablending($img, false);
        imagefilledrectangle($img, 0, 0, $outW * $ss - 1, $outH * $ss - 1, imagecolorallocatealpha($img, 255, 255, 255, 127));
        imagealphablending($img, true);
        $ink  = imagecolorallocate($img, 0x14, 0x15, 0x16);
        $th   = max(1.0, $stroke * $s);
        $half = $th / 2;

        foreach ($lines as $line) {
            $prev = null;
            foreach ($line as [$px, $py]) {
                $cx = ($px - $minX) * $s;
                $cy = ($py - $minY) * $s;
                // Extremos/uniones redondeados.
                imagefilledellipse($img, (int) round($cx), (int) round($cy), (int) round($th), (int) round($th), $ink);
                if ($prev) {
                    [$qx, $qy] = $prev;
                    $len = hypot($cx - $qx, $cy - $qy);
                    if ($len > 0.01) {
                        $nx = -($cy - $qy) / $len * $half;
                        $ny = ($cx - $qx) / $len * $half;
                        imagefilledpolygon($img, [
                            (int) round($qx + $nx), (int) round($qy + $ny),
                            (int) round($cx + $nx), (int) round($cy + $ny),
                            (int) round($cx - $nx), (int) round($cy - $ny),
                            (int) round($qx - $nx), (int) round($qy - $ny),
                        ], $ink);
                    }
                }
                $prev = [$cx, $cy];
            }
        }

        $out = imagecreatetruecolor($outW, $outH);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefilledrectangle($out, 0, 0, $outW - 1, $outH - 1, imagecolorallocatealpha($out, 255, 255, 255, 127));
        imagecopyresampled($out, $img, 0, 0, 0, 0, $outW, $outH, $outW * $ss, $outH * $ss);

        ob_start();
        imagepng($out, null, 9);
        $png = ob_get_clean();
        imagedestroy($img);
        imagedestroy($out);

        return $png ? 'data:image/png;base64,' . base64_encode($png) : null;
    }

    // ── Limpieza de datos ─────────────────────────────────────────────────────

    /** Texto sin etiquetas ni caracteres de control; '' => null. */
    public function clean($value, bool $multiline = false, int $max = 255): ?string
    {
        if ($value === null || ! is_scalar($value)) {
            return null;
        }
        $s = strip_tags((string) $value);
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        $s = preg_replace('/[^\P{C}\n\t]/u', '', $s) ?? '';
        $s = $multiline
            ? preg_replace("/[ \t]+\n/", "\n", $s)
            : preg_replace('/\s+/u', ' ', $s);
        $s = trim((string) $s);

        return $s === '' ? null : mb_substr($s, 0, $max);
    }

    private function cleanAssoc(array $data, array $keys, int $max): array
    {
        $out = [];
        foreach ($keys as $k) {
            if (($v = $this->clean($data[$k] ?? null, false, $max)) !== null) {
                $out[$k] = $v;
            }
        }

        return $out;
    }

    private function cleanReadings(array $data): ?array
    {
        $out = [];
        foreach (self::READING_KEYS as $k) {
            if (($v = $this->numberOrText($data[$k] ?? null, 50)) !== null) {
                $out[$k] = $v;
            }
        }

        return $out ?: null;
    }

    private function cleanMaterials(array $rows): ?array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ($desc = $this->clean($row['descripcion'] ?? null, false, 255)) === null) {
                continue;
            }
            $out[] = [
                'descripcion' => $desc,
                'cantidad'    => $this->numberOrText($row['cantidad'] ?? null, 30),
                'unidad'      => $this->clean($row['unidad'] ?? null, false, 30),
            ];
        }

        return $out ?: null;
    }

    /** "7.5" => 7.5, "12" => 12, "1,5" => 1.5; otro texto se queda como texto. */
    private function numberOrText($value, int $max): int|float|string|null
    {
        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? $value : null;
        }
        $s = $this->clean($value, false, $max);
        if ($s === null) {
            return null;
        }
        $n = str_replace(',', '.', $s);

        return is_numeric($n) ? $n + 0 : $s;
    }

    private function normalize(?string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', Str::lower(Str::ascii((string) $value))));
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $code   = (string) ($e->errorInfo[0] ?? $e->getCode());
        $driver = (int) ($e->errorInfo[1] ?? 0);

        return $driver === 1062 || $code === '23505' || ($code === '23000' && str_contains(strtolower($e->getMessage()), 'unique'));
    }
}
