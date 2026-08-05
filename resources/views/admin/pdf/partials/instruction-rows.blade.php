{{--
    Partial compartido por las 3 hojas de referencia de plantilla
    (products/pdf/template-create, products/pdf/template-update,
    supplier/pdf/template). Recibe $rows — el array plano de filas
    [col1, col2, col3] que devuelve el método instructionRows() de las
    clases *TemplateInstructionsSheet (app/Exports/Sheets/*), el mismo
    contenido que ya se usa en la pestaña "Instrucciones" del Excel.

    $rows no trae marcado de tipo de fila (título/intro/separador/
    encabezado de tabla/fila de campo/sección final/viñeta) — solo texto
    plano en 3 columnas, algunas vacías. Esta vista reconstruye la
    estructura leyendo la forma de cada fila:
      - Fila 0: título grande.
      - Fila 1: párrafo de introducción.
      - Filas en blanco (las 3 columnas vacías): separadores, se ignoran.
      - La primera fila con col2 === 'Obligatorio': encabezado de la
        tabla Columna/Obligatorio/Formato.
      - Mientras la tabla esté "abierta" y la fila tenga col2 no vacío:
        fila de campo (Columna, Obligatorio, Formato).
      - La siguiente fila no vacía tras cerrar la tabla: título de
        sección ("Al importar:").
      - El resto: viñetas (el texto ya trae el prefijo "- ", se recorta).
--}}
@php
    $refTitle = $rows[0][0] ?? '';
    $refIntro = $rows[1][0] ?? '';
    $tableHeader = null;
    $fields = [];
    $sectionTitle = null;
    $bullets = [];

    foreach ($rows as $i => $row) {
        if ($i < 2) {
            continue;
        }

        [$c0, $c1, $c2] = array_pad($row, 3, '');
        $isBlank = trim($c0) === '' && trim($c1) === '' && trim($c2) === '';

        if ($isBlank) {
            continue;
        }

        if ($tableHeader === null && trim($c1) === 'Obligatorio') {
            $tableHeader = [$c0, $c1, $c2];
            continue;
        }

        if ($tableHeader !== null && $sectionTitle === null && trim($c1) !== '') {
            $fields[] = [$c0, $c1, $c2];
            continue;
        }

        if ($sectionTitle === null) {
            $sectionTitle = $c0;
            continue;
        }

        $bullets[] = preg_replace('/^-\s*/', '', $c0);
    }
@endphp

<div class="ref-title">{{ $refTitle }}</div>
<div class="ref-intro">{{ $refIntro }}</div>

@if($tableHeader)
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:24%;">{{ $tableHeader[0] }}</th>
                <th style="width:14%;">{{ $tableHeader[1] }}</th>
                <th style="width:62%;">{{ $tableHeader[2] }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fields as $field)
                <tr>
                    <td class="td-name">{{ $field[0] }}</td>
                    <td>{{ $field[1] }}</td>
                    <td>{{ $field[2] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if($sectionTitle)
    <div class="ref-section">{{ $sectionTitle }}</div>
@endif

@if(count($bullets))
    <ul class="ref-bullets">
        @foreach($bullets as $bullet)
            <li>{{ $bullet }}</li>
        @endforeach
    </ul>
@endif
