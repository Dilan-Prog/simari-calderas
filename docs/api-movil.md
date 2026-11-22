# API móvil v1 (app del personal)

API JSON de solo lectura para la app móvil (React Native / Expo, carpeta `app-movil/`)
que usa el **personal de la empresa** (usuarios del panel, `App\Models\User`).

- Prefijo: `/api/mobile/v1`
- Rutas: `routes/api/mobile.php` (incluidas desde `routes/api.php`, fuera del grupo `/api/v1` de integraciones).
- Controladores: `app/Http/Controllers/Api/Mobile/*` · Recursos: `app/Http/Resources/Mobile/*`.
- Pruebas: `tests/Feature/Mobile/MobileApiTest.php` (`php artisan test --filter=Mobile`).

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
| 422 | Validación / credenciales incorrectas | `{"message": "...", "errors": {"campo": ["..."]}}` |
| 429 | Rate limit excedido | `{"message": "..."}` (header `Retry-After`) |

## Rate limits

- General `mobile`: 120 peticiones/min por usuario autenticado (por IP en rutas sin token).
- Login `mobile-login`: 5 intentos/min por combinación email + IP.

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
  Módulos relevantes para la app: `service-reports` y `technical-services`.

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
- `pdf_url`: URL **firmada y temporal (30 minutos)**. No requiere token: se puede abrir directamente en el
  navegador del teléfono o en un WebView. Si venció, volver a pedir el detalle para obtener otra.

### GET `/reportes/{id}/pdf?expires=...&signature=...`

Pública pero protegida por firma (middleware `signed`). Devuelve el mismo PDF del panel
(vista `admin.service-reports.pdf`) con `Content-Type: application/pdf`, en modo inline.
Sin firma, con firma alterada o vencida → `403` JSON. No construir esta URL a mano: usar `pdf_url`.

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
```
