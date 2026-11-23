{{--
    Equipo, lecturas y materiales capturados en campo (app móvil).
    Uso: @include('admin.service-reports._field-data', ['variant' => 'pdf'|'panel'])
    No imprime nada si el reporte no tiene esos datos (reportes del panel / anteriores).
--}}
@php
    $fdDecode = function ($value) {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        return is_array($value) ? $value : [];
    };
    $fdText = function ($value) {
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }
        return is_scalar($value) ? trim((string) $value) : '';
    };

    $fdEquipmentRaw = $fdDecode($report->equipment ?? null);
    $fdEquipment = [];
    foreach ([
        'tipo' => 'Tipo', 'marca' => 'Marca', 'modelo' => 'Modelo',
        'numero_serie' => 'N.º de serie', 'capacidad' => 'Capacidad', 'ubicacion' => 'Ubicación',
    ] as $fdKey => $fdLabel) {
        $fdValue = $fdText($fdEquipmentRaw[$fdKey] ?? null);
        if ($fdValue !== '') {
            $fdEquipment[] = ['label' => $fdLabel, 'value' => $fdValue];
        }
    }

    $fdReadingsRaw = $fdDecode($report->readings ?? null);
    $fdReadings = [];
    foreach ([
        'presion_operacion' => ['Presión de operación', 'psi'],
        'temperatura_chimenea' => ['Temperatura de chimenea', '°C'],
        'tds' => ['TDS', 'ppm'],
        'ph' => ['pH', ''],
    ] as $fdKey => [$fdLabel, $fdUnit]) {
        $fdValue = $fdText($fdReadingsRaw[$fdKey] ?? null);
        if ($fdValue !== '') {
            // La unidad solo se agrega a valores numéricos; el texto libre se muestra tal cual.
            $fdReadings[] = ['label' => $fdLabel, 'value' => $fdUnit !== '' && is_numeric($fdValue) ? "{$fdValue} {$fdUnit}" : $fdValue];
        }
    }

    $fdMaterials = [];
    foreach ($fdDecode($report->materials ?? null) as $fdItem) {
        if (! is_array($fdItem)) {
            $fdItem = ['descripcion' => $fdItem];
        }
        $fdRow = [
            'descripcion' => $fdText($fdItem['descripcion'] ?? null),
            'cantidad' => $fdText($fdItem['cantidad'] ?? null),
            'unidad' => $fdText($fdItem['unidad'] ?? null),
        ];
        if (implode('', $fdRow) !== '') {
            $fdMaterials[] = $fdRow;
        }
    }

    $fdHasData = $fdEquipment || $fdReadings || $fdMaterials;
@endphp

@if($fdHasData && ($variant ?? 'pdf') === 'pdf')
    <div class="section-title">Equipo, lecturas y materiales</div>

    @if($fdEquipment || $fdReadings)
        <table style="width:100%; border-collapse:collapse; margin-bottom:8px;">
            <tr>
                @if($fdEquipment)
                    <td style="width:{{ $fdReadings ? '50%' : '100%' }}; vertical-align:top; padding:0 {{ $fdReadings ? '8px' : '0' }} 0 0;">
                        <div class="cond-label">Equipo</div>
                        <table class="cf-table">
                            @foreach($fdEquipment as $fdRow)
                                <tr>
                                    <td>{{ $fdRow['label'] }}</td>
                                    <td>{{ $fdRow['value'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                @endif
                @if($fdReadings)
                    <td style="width:{{ $fdEquipment ? '50%' : '100%' }}; vertical-align:top; padding:0 0 0 {{ $fdEquipment ? '8px' : '0' }};">
                        <div class="cond-label">Lecturas</div>
                        <table class="cf-table">
                            @foreach($fdReadings as $fdRow)
                                <tr>
                                    <td>{{ $fdRow['label'] }}</td>
                                    <td>{{ $fdRow['value'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                @endif
            </tr>
        </table>
    @endif

    @if($fdMaterials)
        <div class="cond-label" style="margin-top:6px;">Materiales utilizados</div>
        <table class="meas-table">
            <thead>
                <tr>
                    <th style="width:6%;">#</th>
                    <th style="width:62%;">Descripción</th>
                    <th class="th-right" style="width:16%;">Cantidad</th>
                    <th style="width:16%;">Unidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fdMaterials as $fdIndex => $fdRow)
                    <tr>
                        <td style="text-align:center;color:#aaa;">{{ $fdIndex + 1 }}</td>
                        <td style="font-weight:bold;color:#1a1a2e;">{{ $fdRow['descripcion'] !== '' ? $fdRow['descripcion'] : '—' }}</td>
                        <td style="text-align:right;">{{ $fdRow['cantidad'] !== '' ? $fdRow['cantidad'] : '—' }}</td>
                        <td style="color:#888;">{{ $fdRow['unidad'] !== '' ? $fdRow['unidad'] : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endif

@if($fdHasData && ($variant ?? 'pdf') === 'panel')
    <div class="sr-doc-field-data">
        <p class="sr-doc-section-label">Equipo, lecturas y materiales</p>

        @if($fdEquipment || $fdReadings)
            <div class="sr-doc-info-grid">
                @if($fdEquipment)
                    <div>
                        <p class="sr-doc-table-label" style="margin-bottom:8px;">Equipo</p>
                        <div class="sr-doc-kv-list">
                            @foreach($fdEquipment as $fdRow)
                                <div class="sr-doc-kv-row">
                                    <span class="sr-doc-kv-key">{{ $fdRow['label'] }}</span>
                                    <span class="sr-doc-kv-val">{{ $fdRow['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if($fdReadings)
                    <div>
                        <p class="sr-doc-table-label" style="margin-bottom:8px;">Lecturas</p>
                        <div class="sr-doc-kv-list">
                            @foreach($fdReadings as $fdRow)
                                <div class="sr-doc-kv-row">
                                    <span class="sr-doc-kv-key">{{ $fdRow['label'] }}</span>
                                    <span class="sr-doc-kv-val">{{ $fdRow['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if($fdMaterials)
            <div class="sr-doc-meas-wrap">
                <p class="sr-doc-table-label">Materiales utilizados</p>
                <table class="sr-doc-meas-table">
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th>Cantidad</th>
                            <th>Unidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fdMaterials as $fdRow)
                            <tr>
                                <td class="td-param" data-label="Descripción">{{ $fdRow['descripcion'] !== '' ? $fdRow['descripcion'] : '—' }}</td>
                                <td class="td-result" data-label="Cantidad">{{ $fdRow['cantidad'] !== '' ? $fdRow['cantidad'] : '—' }}</td>
                                <td class="td-unit" data-label="Unidad">{{ $fdRow['unidad'] !== '' ? $fdRow['unidad'] : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
