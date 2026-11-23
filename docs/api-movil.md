# API móvil v1 (app del personal)

API JSON para la app móvil (React Native / Expo, carpeta `app-movil/`) que usa el
**personal de la empresa** (usuarios del panel, `App\Models\User`). Consulta de reportes, agenda, clientes,
usuarios y roles, y alta de reportes de servicio (`POST /reportes`) y de servicios programados (`POST /servicios`).

- Prefijo: `/api/mobile/v1`
- Rutas: `routes/api/mobile.php` (incluidas desde `routes/api.php`, fuera del grupo `/api/v1` de integraciones).
- Controladores: `app/Http/Controllers/Api/Mobile/*` · Recursos: `app/Http/Resources/Mobile/*`.
- Alta de reportes: `app/Services/MobileServiceReportService.php` (validación, mapeo al modelo del panel, firma).
- Pruebas: `tests/Feature/Mobile/MobileApiTest.php`, `MobileReportStoreTest.php` y `MobileCatalogTest.php`
  (`php artisan test --filter=Mobile`).

## Autenticación

1. `POST /login` con email, contraseña y nombre del dispositivo → devuelve un `token`.
2. Enviar el token en **todas** las demás peticiones:

```
Authorization: Bearer <token>
Accept: application/json
```

- Los tokens son tokens personales de Sanctum emitidos a `User` con la ability `mobile`. No expiran
  solos; se revocan con `POST /logout` (o desde la tabla `personal_access_tokens`).
- **Separación de tokens**: un token de cliente de integraciones (`ApiClient`, N8N) recibe `401` en la API
  móvil, y un token de usuario móvil recibe `401` en las rutas `/api/v1/*` de integraciones.
- Si el usuario deja de estar activo (`users.status` ≠ `active`) sus tokens dejan de funcionar (`403`).
- Los permisos son los mismos del panel (`User::hasPermission`, el rol Administrador tiene todo).

## Errores

Todas las respuestas de error son JSON (aunque no se mande `Accept: application/json`), nunca redirects:

| Código | Cuándo | Cuerpo |
|---|---|---|
| 401 | Sin token, token inválido/revocado, token de `ApiClient` o sin ability `mobile` | `{"message": "Unauthenticated."}` / `{"message": "Token no válido para la API móvil."}` |
| 403 | Usuario inactivo/suspendido, sin permiso del módulo, URL de PDF sin firma o vencida | `{"message": "No tienes permiso para acceder a este módulo."}` |
| 404 | Recurso inexistente o no visible, ruta inexistente | `{"message": "..."}` |
| 409 | `POST /servicios`: la `Idempotency-Key` ya la usó otro usuario | `{"message": "La clave de idempotencia ya se usó en otro servicio."}` |
| 422 | Validación / credenciales incorrectas / clave de idempotencia faltante o inválida | `{"message": "...", "errors": {"campo": ["..."]}}` |
| 429 | Rate limit excedido | `{"message": "..."}` (header `Retry-After`) |

## Rate limits

- General `mobile`: 120 peticiones/min por usuario autenticado (por IP en rutas sin token).
- Login `mobile-login`: 5 intentos/min por combinación email + IP.

## Idempotencia (`POST /reportes` y `POST /servicios`)

La app guarda los envíos en una bandeja de salida y los reintenta hasta que el servidor responde. Para que un
reintento (o dos envíos simultáneos) nunca dupliquen el registro, cada alta lleva una clave única por envío:

```
Idempotency-Key: 3f1c2a9e-7b4d-4c1e-9a2f-0d6e5b8c1a47
```

- También se acepta en el cuerpo como `idempotency_key`; si vienen ambas, **gana el header**
  (en `POST /reportes`, si no coinciden → `422`).
- Se guarda en la columna `idempotency_key` (única) de `service_reports` / `services`.
- Primera vez → `201 Created` con `{ "data": ... }`.
- Misma clave otra vez (mismo usuario) → `200 OK` con **el mismo registro** y el header
  `Idempotent-Replayed: true`. No se vuelve a validar el cuerpo ni se crea/sube nada.
- Si dos peticiones con la misma clave llegan a la vez, la que pierde la carrera (choque del índice único)
  responde como reintento (`200` + `Idempotent-Replayed: true`).
- Un `422` de validación **no consume** la clave: el reintento corregido con la misma clave se crea normalmente.

| | `POST /reportes` | `POST /servicios` |
|---|---|---|
| Clave | **Obligatoria** (`422` `idempotency_key` si falta) | Opcional: sin clave, cada envío crea un servicio nuevo |
| Formato | Máx. 100 caracteres: letras, números, `.`, `_`, `:` o `-` (un UUID sirve) | Máx. 100 caracteres |
| Clave usada por otro usuario | `422` en `idempotency_key` («ya fue usada por otro usuario») | `409` |

## Formato multipart de la app (bandeja de salida)

La app manda las altas como `multipart/form-data` con los objetos **aplanados en notación de corchetes**
(`flatten()` de `src/lib/outbox.ts`); todos los valores llegan como texto y el servidor convierte números/fechas.
También se acepta JSON equivalente (sin archivos) en `POST /servicios`.

```
cliente[nombre]=Hotel Fiesta
cliente[empresa]=Fiesta SA de CV
equipo[tipo]=Caldera
materiales[0][descripcion]=Empaque
materiales[0][cantidad]=2
materiales[0][unidad]=pza
lecturas[presion_operacion]=120
fotos[]=<archivo JPEG>
fotos[]=<archivo JPEG>
fotos_meta[0][orden]=1
fotos_meta[0][descripcion]=Antes
fotos_meta[1][orden]=2
fotos_meta[1][descripcion]=Después
firma[nombre]=Juan Pérez
firma[trazo]=M10 40 L30.5 12 L52 44
firma=<archivo firma.svg>
```

- `fotos[]` y `fotos_meta[i]` van **en el mismo orden**: la foto `i` toma el pie de foto de `fotos_meta[i]`.
- `firma` llega a la vez como arreglo de texto (`firma[nombre]`, `firma[trazo]`, …) y como **archivo** `firma`
  (el SVG de la firma). El servidor lee ambos por separado.
- El header `X-Outbox-Type` (p. ej. `service_report.create`) que pueda mandar la bandeja es informativo; el servidor no lo usa.

---

## Endpoints

### POST `/login`

Público. Body JSON:

```json
{ "email": "tecnico@equiterm.mx", "password": "secreto", "device_name": "Pixel 8 de Juan" }
```

`200 OK`:

```json
{
  "token": "12|kq8x0b...",
  "user": {
    "id": 7,
    "name": "Juan Pérez",
    "email": "tecnico@equiterm.mx",
    "role": { "id": 3, "name": "Técnico" },
    "is_admin": false,
    "permissions": {
      "service-reports": ["view", "create", "edit"],
      "technical-services": ["view"]
    }
  }
}
```

- `422` `{"message": "Credenciales incorrectas"}` si el email no existe o la contraseña no coincide.
- `422` con `errors` si falta `email`, `password` o `device_name`.
- `403` `{"message": "Tu cuenta está desactivada. Contacta al administrador."}` si `status` es `inactive` o `suspended`
  (solo se informa con la contraseña correcta).
- El token se guarda con nombre = `device_name` y abilities `["mobile"]`.

### POST `/logout`

Revoca solo el token usado en la petición. `204 No Content` (sin cuerpo).

### GET `/me`

`200 OK`: `{ "user": { ...misma forma que en login... } }`

- `role`: `{id, name}` o `null`. `name` es el nombre en español del rol (o el nombre interno si no tiene).
- `is_admin`: `true` para el rol Administrador; en ese caso `permissions` lista todo el catálogo de permisos.
- `permissions`: objeto `{ "<módulo>": ["view", "create", "edit", "delete", "log"] }`; `{}` si no tiene ninguno.
  Módulos relevantes para la app: `service-reports`, `technical-services`, `clients`, `users` y `roles`.

### GET `/reportes`

Requiere permiso `service-reports` · `view` (si no, `403`).

Query: `search` (opcional, busca en folio, nombre del cliente y empresa, igual que el panel),
`page` (≥1), `per_page` (1–50, por defecto 20). Orden: fecha de servicio descendente.

Visibilidad: igual que el panel web, que hoy **no** acota por técnico (quien tiene `service-reports,view` ve todos).

`200 OK`:

```json
{
  "data": [
    {
      "id": 42,
      "folio": "RS-2026-0042",
      "status": "signed",
      "status_label": "Firmado",
      "service_date": "2026-11-20",
      "customer": { "id": 15, "name": "Planta Norte", "company": "Norte SA de CV" },
      "technician": { "id": 7, "name": "Juan Pérez" },
      "service_type": "Mantenimiento Preventivo",
      "service_type_key": "maintenance_preventive",
      "is_signed": true,
      "updated_at": "2026-11-20T17:45:10-06:00"
    }
  ],
  "meta": { "current_page": 1, "last_page": 3, "per_page": 20, "total": 47 }
}
```

- `status`: `draft` (Borrador), `in_progress` (En Proceso), `completed` (Completado), `signed` (Firmado).
- `customer`: datos capturados en el reporte (`name`/`company`), `id` del cliente vinculado o `null`; `customer` es `null` si el reporte no tiene cliente.
- `technician`: usuario asignado o `null`. `service_date`: `Y-m-d` o `null`.

### GET `/reportes/{id}`

Mismo permiso. `404` si no existe o no es visible. `200 OK` → `{ "data": { ... } }` con todos los campos del
listado más el detalle que muestra la vista web:

```json
{
  "data": {
    "id": 42,
    "folio": "RS-2026-0042",
    "status": "signed",
    "status_label": "Firmado",
    "service_date": "2026-11-20",
    "customer": { "id": 15, "name": "Planta Norte", "company": "Norte SA de CV", "rfc": "NOR010101AAA", "phone": "4771234567" },
    "technician": { "id": 7, "name": "Juan Pérez" },
    "service_type": "Mantenimiento Preventivo",
    "service_type_key": "maintenance_preventive",
    "is_signed": true,
    "updated_at": "2026-11-20T17:45:10-06:00",
    "custom_service_type": null,
    "form_behavior": "activity",
    "week_number": 47,
    "location": "Cuarto de máquinas",
    "created_by": { "id": 2, "name": "Ana Admin" },
    "technical_service": { "id": 90, "service_number": "SERV-2026-0090" },
    "observations": "Fuga menor en válvula de alivio",
    "recommendations": "Cambiar empaque en la próxima visita",
    "sampling": { "date": "20/11/2026", "analyst_name": "Q. Laura Ríos", "analyst_position": "Analista" },
    "measurements": [
      { "id": 1, "parameter": "pH", "unit": null, "result": "8.1", "limit_min": 7.0, "limit_max": 8.5, "in_range": true }
    ],
    "activity": { "content": "Se revisó quemador y bomba", "systems_checked": ["Quemador", "Bomba"] },
    "custom_fields": [ { "id": 3, "name": "Presión", "type": "text", "value": "120 psi" } ],
    "signature": {
      "name": "Ing. Pedro Gómez",
      "position": "Jefe de planta",
      "phone": "4770000000",
      "signed_at": "2026-11-20T17:40:00-06:00",
      "image": "data:image/png;base64,iVBORw0..."
    },
    "rating": { "rating": 5, "card_label": "Excelente", "comment": "Muy puntuales", "submitted_at": "2026-11-21T10:00:00-06:00" },
    "equipment": { "tipo": "Caldera", "marca": "Cleaver-Brooks", "modelo": "CB-200", "numero_serie": "SN-123", "capacidad": "200 CC", "ubicacion": "Cuarto de máquinas" },
    "readings": { "presion_operacion": 120, "temperatura_chimenea": 180.5, "tds": "alto" },
    "materials": [ { "descripcion": "Empaque", "cantidad": 2, "unidad": "pza" }, { "descripcion": "Sellador", "cantidad": 0.5, "unidad": "lt" } ],
    "origin": "app_movil",
    "captured_at": "2026-11-20T10:45:00-06:00",
    "images": [ { "id": 10, "url": "http://192.168.1.50:8000/media/uploads/service-reports/foto.jpg", "caption": "Antes" } ],
    "created_at": "2026-11-20T09:00:00-06:00",
    "pdf_url": "http://192.168.1.50:8000/api/mobile/v1/reportes/42/pdf?expires=1795000000&signature=abc123..."
  }
}
```

- `form_behavior` (`measurements` | `activity` | `custom` | `null`) indica qué bloque aplica según el tipo de reporte;
  igual que en la vista web, `measurements` solo viene lleno en tipo `measurements`, `activity` solo en `activity`
  y `custom_fields` solo en `custom` (los demás vienen `[]` / `null`).
- `sampling` es `null` si el reporte no incluye muestreo. `signature` solo existe en reportes firmados.
  `rating` solo si el cliente ya envió su calificación.
- Datos de campo capturados en la app (vienen vacíos en reportes hechos en el panel o anteriores a la app):
  - `equipment`: `{tipo, marca, modelo, numero_serie, capacidad, ubicacion}` (solo las claves capturadas) o `null`.
  - `readings`: `{presion_operacion, temperatura_chimenea, tds, ph}`; cada valor es número si se pudo
    interpretar (`"0,5"` → `0.5`) o texto si no; las vacías se omiten; `null` si no hay ninguna.
  - `materials`: lista `[{descripcion, cantidad, unidad}]` (`cantidad` número o texto, `unidad` texto o `null`); `[]` si no hay.
  - `origin`: `"app_movil"` para reportes dados de alta con `POST /reportes`; `null` = panel.
  - `captured_at`: cuándo se capturó en el teléfono (ISO 8601), puede ser horas antes del envío; `null` en reportes del panel.
- `images[].caption`: pie de foto (de `fotos_meta[i][descripcion]` en la app) o `null`.
- `service_type_key` puede ser cualquier key de `service_report_types`, incluida `startup` («Arranque»).
- `pdf_url`: URL **firmada y temporal (30 minutos)**. No requiere token: se puede abrir directamente en el
  navegador del teléfono o en un WebView. Si venció, volver a pedir el detalle para obtener otra.

### GET `/reportes/{id}/pdf?expires=...&signature=...`

Pública pero protegida por firma (middleware `signed`). Devuelve el mismo PDF del panel
(vista `admin.service-reports.pdf`) con `Content-Type: application/pdf`, en modo inline.
Sin firma, con firma alterada o vencida → `403` JSON. No construir esta URL a mano: usar `pdf_url`.

### POST `/reportes`

Alta de un reporte completo (normalmente ya firmado) capturado en el asistente de 5 pasos de la app.
Requiere permiso `service-reports` · `create` (si no, `403`). **Idempotente**: header `Idempotency-Key`
obligatorio (ver [Idempotencia](#idempotencia-post-reportes-y-post-servicios)). Cuerpo `multipart/form-data`
aplanado (ver [Formato multipart](#formato-multipart-de-la-app-bandeja-de-salida)).

El reporte queda igual que uno hecho en el asistente web (mismo folio `RS-…`, bloque de actividad, imágenes y PDF).

| Campo | Tipo | Notas |
|---|---|---|
| `tipo_servicio` | texto, **requerido** | Ver mapeo abajo. Solo tipos con formulario de actividades. |
| `trabajos_realizados` | texto, **requerido**, máx. 10 000 | Contenido del bloque de actividad. Vacío tras limpiar → `422`. |
| `hallazgos` | texto, máx. 10 000 | Se guarda como `observations`. |
| `recomendaciones` | texto, máx. 10 000 | Se guarda como `recommendations`. |
| `capturado_en` | fecha ISO 8601 | Fecha/hora de captura en el teléfono; define `service_date`, `week_number` y `captured_at`. Por defecto, ahora. |
| `servicio_id` | entero | Servicio técnico vinculado; debe existir con estatus `scheduled`, `in_progress` o `completed`. |
| `cliente_id` / `cliente[id]` | entero | Cliente del catálogo (`exists:customers`). Si vienen ambos deben coincidir. |
| `cliente[nombre]` | texto, máx. 200 | Requerido si no hay cliente del catálogo ni servicio con cliente. |
| `cliente[empresa]` | texto, máx. 200 | Solo se usa sin cliente del catálogo. |
| `cliente[ubicacion]` | texto, máx. 200 | Se guarda como `location` (si falta, se usa `equipo[ubicacion]`). |
| `equipo[tipo\|marca\|modelo\|numero_serie\|capacidad]` | texto, máx. 100 | → `equipment`. |
| `equipo[ubicacion]` | texto, máx. 200 | → `equipment.ubicacion`. |
| `lecturas[presion_operacion\|temperatura_chimenea\|tds\|ph]` | número o texto, máx. 50 | → `readings`. |
| `materiales[i][descripcion\|cantidad\|unidad]` | máx. 100 filas | → `materials`; las filas sin `descripcion` se descartan. |
| `fotos[]` | archivos, máx. 20 | JPEG/PNG/WebP, máx. 5 MB cada una. |
| `fotos_meta[i][orden]`, `fotos_meta[i][descripcion]` | entero ≥ 0 / texto máx. 255 | Orden y pie de foto de `fotos[i]`. |
| `firma[nombre]` | texto, máx. 150 | **Requerido si hay firma**. |
| `firma[trazo]` | texto, máx. 500 000 | Atributo `d` del path SVG de la firma (comandos `M`/`L`). |
| `firma[ancho]`, `firma[alto]` | número 1–5000 | Tamaño del lienzo (informativo). |
| `firma[firmado_en]` | fecha ISO 8601 | `signed_at`; por defecto `capturado_en`. |
| `firma` (archivo) | máx. 1 MB | SVG de la firma (o PNG/JPEG). |
| `tecnico_id`, `origen` | — | Se aceptan pero **se ignoran** (ver abajo). |

**Mapeo de `tipo_servicio`** (sin distinguir mayúsculas ni acentos):

| Valor de la app | `service_type_key` |
|---|---|
| `Preventivo`, `Mantenimiento preventivo` | `maintenance_preventive` |
| `Correctivo`, `Mantenimiento correctivo` | `maintenance_corrective` |
| `Inspección` / `Inspeccion` | `inspection` |
| `Arranque`, `Puesta en marcha` | `startup` |

Si no está en la tabla se busca un tipo activo de `service_report_types` por su `key` exacta o por su etiqueta.
El tipo debe tener `form_behavior = activity`; si no existe, está inactivo o es de mediciones/personalizado
(p. ej. `chemical_analysis`) → `422` en `tipo_servicio`.

**Cómo se arma el reporte:**

- **Técnico**: `assigned_user_id` y `created_by_user_id` = usuario del token. `tecnico_id` se ignora (evita crear
  reportes a nombre de terceros).
- **Cliente / servicio**: con `servicio_id`, el cliente se toma **del servicio** (igual que el panel); si no coincide
  con el elegido en la app se agrega un `warning`. Con cliente del catálogo, nombre, empresa, RFC y teléfono salen
  del catálogo (`cliente[nombre]` solo como respaldo). Sin cliente del catálogo se usan `cliente[nombre]` y
  `cliente[empresa]`; si no hay nombre → `422` en `cliente.nombre`.
- **Actividad**: `trabajos_realizados` → contenido; `systems_checked = ["calderas"]` si `equipo[tipo]` contiene «caldera».
- **Fotos**: se emparejan `fotos[i]` ↔ `fotos_meta[i]` y se ordenan por `orden`; `descripcion` queda como `caption`.
  Una foto que no se puede procesar se omite y genera un `warning` (el reporte sí se guarda).
- **Firma**: se toma `firma[trazo]`; si falta, los `d` de los paths del archivo SVG `firma`; si el archivo es PNG/JPEG
  se usa tal cual. El trazo se **rasteriza a PNG** (fondo transparente, máx. 900×360) y se guarda como
  `data:image/png;base64,...` en `signature_data` — el mismo formato que el panel, el único que pintan igual DomPDF
  y el `<Image>` de React Native. `signature_position` = `Cliente`. Si la firma no se puede interpretar, el reporte
  se guarda sin firma y con un `warning`.
- **Estatus**: `signed` si hay firma; `completed` si no.
- `origin` = `app_movil` siempre; `captured_at` = `capturado_en`.
- Los textos se limpian (sin etiquetas HTML ni caracteres de control).

`201 Created` → mismo cuerpo que [`GET /reportes/{id}`](#get-reportesid), más `warnings` solo si hubo avisos:

```json
{
  "data": {
    "id": 43,
    "folio": "RS-2026-0043",
    "status": "signed",
    "service_type": "Mantenimiento Preventivo",
    "service_type_key": "maintenance_preventive",
    "form_behavior": "activity",
    "origin": "app_movil",
    "captured_at": "2026-11-20T10:45:00-06:00",
    "images": [ { "id": 11, "url": "http://192.168.1.50:8000/media/uploads/service-reports/43/foto.jpg", "caption": "Antes" } ],
    "signature": { "name": "Juan Pérez", "position": "Cliente", "phone": null, "signed_at": "2026-11-20T10:44:00-06:00", "image": "data:image/png;base64,iVBORw0..." },
    "...": "resto de campos del detalle"
  },
  "warnings": [
    "No se pudo procesar la foto 1 (formato no compatible o archivo dañado); el reporte se guardó sin ella."
  ]
}
```

- `warnings` (no fatales, el reporte se guardó): foto no procesable, «No se pudo interpretar la firma; el reporte
  se guardó sin firma.», «El cliente se tomó del servicio vinculado (no coincidía con el elegido en la app).».
- `200 OK` + `Idempotent-Replayed: true` → reintento con la misma clave: `{ "data": ... }` del reporte ya creado (sin `warnings`).
- `422`: clave de idempotencia faltante/inválida/de otro usuario, `tipo_servicio` no válido, `trabajos_realizados`
  vacío, `cliente_id` o `servicio_id` inexistente, falta `cliente.nombre`, falta `firma.nombre` con firma,
  textos demasiado largos, `fotos.N` que no es imagen o excede 5 MB, etc.
- `403` sin `service-reports` · `create` · `401` sin token.

### GET `/clientes`

Buscador de clientes (paso «Cliente» del asistente de reportes y «Programar servicio»). Solo lectura, solo
clientes con `status = active`. Requiere **cualquiera** de: `service-reports` · `create`, `technical-services` · `create`
o `clients` · `view` (se revisa en el controlador; si no tiene ninguno, `403`).

Query: `search` (opcional, máx. 100; busca en nombre, apellido, nombre completo, empresa, RFC, email y teléfono),
`page` (≥1), `per_page` (1–100, por defecto 20). Orden: empresa (o nombre completo si no tiene) ascendente.

`200 OK`:

```json
{
  "data": [
    {
      "id": 15,
      "name": "Norte SA de CV",
      "full_name": "Cli Ente",
      "contact_name": "Cli Ente",
      "company": "Norte SA de CV",
      "rfc": "NOR010101AAA",
      "phone": "4771234567",
      "email": "compras@norte.mx",
      "address": "Av. Industrias 123, Int. 4, León, Guanajuato, C.P. 37000",
      "city": "León",
      "state": "Guanajuato",
      "location": "León, Guanajuato"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1 }
}
```

- `name`: lo que muestra la app — la empresa si existe y, si no, la persona. La persona va en `full_name` / `contact_name`.
- `address`, `city`, `state` salen de la dirección predeterminada del cliente (`null` si no tiene);
  `location` = `"ciudad, estado"`.
- `422` si `per_page` > 100 o `search` excede 100 caracteres.

### GET `/servicios`

Agenda de servicios técnicos. Requiere permiso `technical-services` · `view`.

Query: `from` y `to` en formato `Y-m-d` (por defecto de hoy a hoy + 14 días; `to` ≥ `from`; rango máximo 92 días).
Visibilidad: igual que el calendario del panel (todos los servicios del rango, sin acotar por técnico).
Orden: fecha y hora ascendente.

`200 OK`:

```json
{
  "data": [
    {
      "id": 90,
      "title": "Mantenimiento de caldera",
      "service_number": "SERV-2026-0090",
      "status": "scheduled",
      "status_label": "Programado",
      "priority": "high",
      "priority_label": "Alta",
      "is_customer_request": false,
      "scheduled_at": "2026-11-25T10:30:00-06:00",
      "estimated_duration": 4.0,
      "customer": { "id": 15, "name": "Cli Ente", "company": "Norte SA de CV" },
      "technician": { "id": 7, "name": "Juan Pérez" },
      "technicians": [ { "id": 7, "name": "Juan Pérez" } ],
      "address": "Av. Industrias 123, León, Gto.",
      "reference": "Puerta 4",
      "description": "Mantenimiento preventivo anual"
    }
  ],
  "meta": { "from": "2026-11-22", "to": "2026-12-06", "total": 1 }
}
```

- `title`: nombre del tipo de servicio (como el calendario del panel), o el folio si no tiene tipo.
- `status`: `draft` (Borrador / "Solicitud enviada" si viene del portal), `scheduled`, `in_progress`, `completed`, `cancelled`.
- `scheduled_at`: fecha + hora del servicio en ISO 8601 (si no tiene hora, `00:00`); `null` sin fecha.
- `technician`: primer técnico asignado (o `null`); `technicians`: todos los asignados.
- `422` si `from`/`to` no tienen formato `Y-m-d`, si `to` < `from` o si el rango excede 92 días.

### POST `/servicios`

«Programar servicio» desde la app. Requiere permiso `technical-services` · `create` (si no, `403`).
**Idempotente** con `Idempotency-Key` opcional (ver [Idempotencia](#idempotencia-post-reportes-y-post-servicios)).
Acepta el multipart aplanado de la bandeja o JSON.

Reutiliza el alta del panel (`TechnicalServiceService::store`: folio `SVC-AAAA-NNNN`, bitácora) y deja el servicio
como el asistente del panel al confirmar el paso 4: técnicos asignados, estatus `scheduled`, `current_step = 4`,
sin `draft_token`. `created_by_user_id` = usuario del token.

| Campo | Tipo | Notas |
|---|---|---|
| `customer_id` | entero, **requerido** | Cliente del catálogo. Desde la app no se dan de alta clientes nuevos. |
| `service_type` | texto | Nombre del tipo de servicio (ver abajo). Alternativa a `service_type_id`. |
| `service_type_id` | entero | Requerido si no viene `service_type`. |
| `scheduled_at` | `Y-m-d H:i[:s]` o ISO 8601 | Se separa en `service_date` + `service_time` (zona horaria de la app). Alternativa a los dos siguientes. |
| `service_date` | `Y-m-d` | Requerido si no viene `scheduled_at`. |
| `service_time` | `H:i` (o `H:i:s`) | Opcional. |
| `technician_id` | entero | Usuario activo. |
| `technician_ids[]` | enteros | Usuarios activos; se combinan con `technician_id`. |
| `priority` | `normal` \| `high` \| `urgent` | Por defecto `normal`. |
| `estimated_duration` | texto, máx. 20 | |
| `description` | texto, máx. 5000 | |
| `address`, `reference` | texto, máx. 255 | |
| `location` | texto, máx. 200 | |
| `title`, `customer_name`, `technician_name` | — | Los manda la app como apoyo; se ignoran. |

**Tipo de servicio por nombre** (`service_types` activos, sin distinguir mayúsculas): primero coincidencia exacta y
luego **parcial** (el nombre del tipo contiene el texto; p. ej. `Preventivo` → «Mantenimiento Preventivo», el primero
en orden alfabético). Un valor numérico se toma como id. Sin coincidencia → `422` en `service_type` con la lista de
opciones en el mensaje.

Ejemplo (como lo arma la app):

```
POST /api/mobile/v1/servicios
Idempotency-Key: k-1

service_type=Preventivo
customer_id=15
customer_name=Planta Norte
scheduled_at=2026-11-25 10:30:00
technician_id=7
technician_name=Ana Ruiz
address=Av. Industrial 5
description=Revisar quemador
```

`201 Created` → `{ "data": { ... } }` con la **misma forma que cada elemento de `GET /servicios`**
(`status: "scheduled"`, `service_number: "SVC-2026-0091"`, `title` = nombre del tipo, `scheduled_at` ISO 8601…).

- `200 OK` + `Idempotent-Replayed: true` → misma clave del mismo usuario: el servicio ya creado.
- `409` si la clave ya la usó otro usuario (no se revela su servicio).
- `422`: falta `customer_id` («Elige un cliente del catálogo…»), tipo no reconocido, falta fecha, `scheduled_at`
  inválido, técnico inexistente o inactivo, `priority` fuera de la lista, clave de más de 100 caracteres.
- `403` sin `technical-services` · `create`.

### GET `/usuarios`

Usuarios del personal (solo lectura). Requiere permiso `users` · `view`, como el panel (si no, `403`).

Query: `search` (opcional, máx. 100; nombre, apellido, nombre completo, email y puesto), `status`
(`active` | `inactive` | `suspended`), `role_id`, `page` (≥1), `per_page` (1–100, por defecto **50**).
Orden: nombre y apellido ascendente.

`200 OK`:

```json
{
  "data": [
    {
      "id": 7,
      "name": "Juan Pérez",
      "first_name": "Juan",
      "last_name": "Pérez",
      "email": "tecnico@equiterm.mx",
      "phone": "4771234567",
      "position": "Técnico de campo",
      "status": "active",
      "is_active": true,
      "role": { "id": 3, "name": "Técnico" },
      "is_admin": false,
      "last_seen_at": "2026-11-21T18:02:11-06:00",
      "created_at": "2026-01-10T09:00:00-06:00"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 50, "total": 1 }
}
```

- Lista blanca de campos: nunca contraseña, tokens ni datos sensibles (RFC, CURP, NSS, fecha de nacimiento).
- `last_seen_at`: último uso de un token de la app (no hay registro de accesos al panel); `null` si nunca la usó.
- `role.name`: nombre en español del rol (o el interno).

### GET `/roles`

Roles con su número de usuarios y matriz de permisos (solo lectura). Requiere permiso `roles` · `view` (si no, `403`).
Sin paginación ni filtros (son pocos). Orden: `id`.

`200 OK`:

```json
{
  "data": [
    {
      "id": 3,
      "name": "Técnico",
      "key": "Tecnico",
      "description": "Personal de campo",
      "users_count": 5,
      "is_admin": false,
      "permissions": {
        "service-reports": ["view", "create"],
        "technical-services": ["view"]
      }
    }
  ],
  "meta": { "total": 4 }
}
```

- `name`: nombre en español (o el interno); `key`: nombre interno del rol.
- `permissions`: mismo formato que `/me`; `{}` si no tiene ninguno. El rol administrador recibe el catálogo completo.

---

## Cambios de base de datos (migraciones `2026_11_22_*`)

- `2026_11_22_200000_add_mobile_capture_fields_to_service_reports_table`: columnas nuevas en `service_reports`:
  `equipment` (JSON), `readings` (JSON), `materials` (JSON), `origin` (VARCHAR 20), `captured_at` (TIMESTAMP)
  e `idempotency_key` (VARCHAR 100, **única**). Todas nullable.
- `2026_11_22_200100_add_startup_service_report_type`: `service_reports.service_type` pasa de ENUM (8 valores
  originales) a **VARCHAR(60)** en MySQL — antes, en modo estricto, cualquier key nueva de `service_report_types`
  fallaba con "Data truncated" — y se agrega el tipo de reporte **«Arranque»** (key `startup`,
  `form_behavior = activity`) si no existía.
- `2026_11_22_300000_add_idempotency_key_to_services_table`: `services.idempotency_key` (VARCHAR 100, nullable, **única**)
  para `POST /servicios` (no se reutiliza `draft_token` porque el panel lo borra al programar).

---

## Probar desde el teléfono (Laragon / red local)

1. En la PC con Laragon, abrir la terminal en la carpeta del proyecto y levantar el servidor escuchando en
   todas las interfaces:

   ```
   php artisan serve --host=0.0.0.0 --port=8000
   ```

2. Obtener la IP LAN de la PC (`ipconfig` en Windows → "Dirección IPv4", p. ej. `192.168.1.50`).
3. En la app (`app-movil/`) usar como URL base `http://192.168.1.50:8000/api/mobile/v1`.
   El teléfono debe estar en la **misma red Wi-Fi** que la PC.
4. Si no conecta: permitir PHP / el puerto 8000 en el Firewall de Windows (red privada) y verificar desde el
   navegador del teléfono que `http://192.168.1.50:8000` responde.
5. Las URLs de imágenes y `pdf_url` se generan con el host con que llega la petición (la IP LAN), así que
   funcionan desde el teléfono sin cambiar `APP_URL`.

Ejemplo rápido con curl:

```bash
curl -X POST http://192.168.1.50:8000/api/mobile/v1/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"tecnico@equiterm.mx","password":"secreto","device_name":"curl"}'

curl http://192.168.1.50:8000/api/mobile/v1/reportes?per_page=5 \
  -H "Accept: application/json" -H "Authorization: Bearer <token>"

curl -X POST http://192.168.1.50:8000/api/mobile/v1/reportes \
  -H "Accept: application/json" -H "Authorization: Bearer <token>" \
  -H "Idempotency-Key: prueba-curl-1" \
  --form-string "tipo_servicio=Arranque" \
  --form-string "trabajos_realizados=Arranque de caldera y ajuste de quemador" \
  --form-string "cliente[nombre]=Hotel Fiesta" \
  --form-string "materiales[0][descripcion]=Empaque" --form-string "materiales[0][cantidad]=2" \
  -F "fotos[]=@foto1.jpg" --form-string "fotos_meta[0][descripcion]=Antes" \
  --form-string "firma[nombre]=Juan Pérez" --form-string "firma[trazo]=M10 40 L30 12 L52 44"
```
