# Correo de leads con n8n y Automatizaciones

Cómo se conectan el buzón de la empresa, n8n y la plataforma. Todo usa un token de **Cliente de API** con la habilidad `leads:write` (Integraciones > Clientes de API). El token va en `Authorization: Bearer <TOKEN>`; nunca se guarda en el repositorio.

## Los flujos

| Flujo (archivo) | Qué hace |
|---|---|
| Recepción (`equiterm-recepcion-de-correos.json`, o `equiterm-recepcion-de-correos-webhook.json` si el buzón avisa por webhook de Hostinger) | Cada correo nuevo llega a `POST /api/v1/leads`: crea el lead (o reutiliza el abierto de ese correo), una tarea para el vendedor y guarda el correo entrante. |
| Enviados (`equiterm-correos-enviados.json`) | Lee la carpeta Enviados y manda a `POST /api/v1/leads/outbound-email` lo que se escribió desde el correo normal. Solo se guarda si el destinatario es un cliente con tarea. |
| Envío por API (nuevo, sin archivo: es un nodo HTTP) | n8n (o una automatización) pide a la plataforma enviar un correo con `POST /api/v1/leads/{lead}/send-email`. Sale por el SMTP de Integraciones > Correo y queda en el historial del lead y de la tarea. |

## Endpoint: enviar correo a un lead

`POST /api/v1/leads/{lead}/send-email` (`{lead}` es el ID numérico; límite 30 por minuto además del límite general de la API).

Cuerpo JSON:

| Campo | Obligatorio | Notas |
|---|---|---|
| `subject` | Sí, salvo con `template_id` | Máx. 255. Tiene prioridad sobre la plantilla. |
| `body` | Sí, salvo con `template_id` | Texto plano. Se agrega la firma de Integraciones > Correo. |
| `template_id` | No | ID de una plantilla de correo; completa asunto/cuerpo con los datos del contacto (`{{contact.name}}`, etc.). |
| `to` | No | Por defecto el correo del lead. |
| `cc` | No | Arreglo de correos, máximo 5. |
| `close_task` | No | `true` cierra la tarea ligada. Por defecto no se cierra. |
| `quote_ids` | No | IDs de cotizaciones del cliente del lead; se adjuntan en PDF. |

```bash
curl -X POST "https://TU-DOMINIO/api/v1/leads/123/send-email" \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"subject":"Recibimos tu solicitud","body":"Hola, ya tenemos tu mensaje y un asesor te contactará hoy."}'
```

Respuesta `201`:

```json
{ "data": { "outbound_email_id": 45, "message_id": "<uuid@dominio>", "task_id": 77, "lead_id": 123 } }
```

Errores: `401` sin token, `403` token sin `leads:write`, `404` lead inexistente, `422` datos inválidos o no se puede enviar (`{"message": "..."}` en español: SMTP sin configurar, lead sin correo, cotización ajena, etc.). El correo se guarda con origen `platform` y sin usuario.

## Disparar automatizaciones desde correos y leads

Automatizaciones (Workflows) ya puede usar estos módulos del grupo CRM:

- **Lead** (`lead`): eventos creado / actualizado / sin actividad (`last_activity_at`).
- **Correo recibido de lead** (`lead_inbound_email`): `created` al llegar un correo. Tokens: `{{ subject }}`, `{{ from_email }}`, `{{ excerpt }}`, `{{ lead_id }}`, `{{ lead.contact_email }}`, `{{ lead.contact_name }}`.
- **Correo enviado a lead** (`lead_outbound_email`): `created` al registrarse un envío (desde la plataforma o desde Enviados). Mismos tokens de `lead.*`, más `{{ source }}`.

Para llamar a n8n use el paso **Llamar webhook**. Registre la URL del webhook de n8n en el catálogo de Webhooks de Integraciones (así se reutiliza) y, si n8n pide autenticación, una credencial tipo `webhook` con el nombre y valor del encabezado. El cuerpo del paso lleva los tokens, por ejemplo `lead_id = {{ lead_id }}`.

### Ejemplos

1. **Acuse de recibo al llegar un correo.** Workflow de tipo `lead_inbound_email`, disparador `{"event":"created"}`, paso **Esperar 1 minuto** y luego **Llamar webhook** a n8n con `lead_id`. En n8n, un nodo HTTP hace `POST /api/v1/leads/{lead_id}/send-email` con `template_id` del acuse. La espera es importante: el evento se dispara dentro de la transacción que crea el lead y una llamada inmediata de n8n aún no lo vería (404).
2. **Recordatorio si un lead lleva 24 h sin respuesta.** Workflow de tipo `lead`, disparador `{"event":"stale","hours":24,"field":"reply_pending_at"}` (`reply_pending_at` solo tiene valor mientras el último correo del cliente sigue sin contestar), seguido de una condición `status` igual a `nuevo` y del paso Llamar webhook / crear tarea. La revisión corre con `automatable-modules:tick` (programado). Con `last_activity_at` en lugar de `reply_pending_at` se inscriben también los leads ya cerrados: añada la condición por estado.
3. **Seguimiento de cotización.** Workflow de tipo `quote` (ya existente), disparador `{"event":"stale","hours":72,"field":"sent_at"}`, condición `status` igual a `sent`, Llamar webhook a n8n que envía el correo de seguimiento por `send-email` con `quote_ids` si el cliente tiene un lead.

### Cuidado con los ciclos

El motor **no tiene guarda de recursión**. Cada envío por la API crea un `lead_outbound_email` nuevo, que es una fila distinta cada vez, así que la opción "no reinscribir" no lo detiene. Un workflow de tipo `lead_outbound_email` (o de `lead`, porque el envío actualiza el lead) que a su vez envíe correo por la API se dispararía sin fin. Reglas:

- Un workflow que envía correo no debe escuchar el mismo tipo de evento que genera.
- Si necesita reaccionar a correos enviados, agregue una condición `source` igual a `external` (solo lo escrito desde el correo normal). Está cubierto en `tests/Feature/Crm/LeadEmailApiTest.php`.
- Para `lead_inbound_email` el riesgo es bajo: enviar un correo no crea uno entrante.

## Duplicados y Message-ID

- Recepción: `message_id` es la cabecera Message-ID del correo. Reenviar el mismo devuelve `200` con `duplicate: true` y no crea otro lead, tarea ni evento de automatización.
- Enviados: igual, un `message_id` repetido no se guarda dos veces.
- Los correos enviados por la API llevan un Message-ID propio; si el flujo de Enviados ve ese mismo correo, lo ignora por ese Message-ID.

## Límites

- El envío por SMTP **no queda en la carpeta Enviados** del buzón; vive en la plataforma (historial del lead y de la tarea).
- Un correo de la plataforma **no cierra la tarea** salvo que se mande `close_task: true`.
- Si el SMTP no está configurado (Integraciones > Correo) el endpoint responde `422` y no registra nada.
- **Remitente de las respuestas:** las respuestas desde la bandeja y este endpoint salen del buzón configurado en Integraciones > Correo > «Buzón de la bandeja de leads» (normalmente `administracion@`, mismo servidor SMTP, con su propio usuario/contraseña) y llevan ese mismo Reply-To. Si ese buzón no está configurado salen con el remitente general (`no-reply@`) y la bandeja muestra un aviso. El resto de correos del sistema siguen usando el remitente general.
- El paso Llamar webhook es síncrono (15 s de tiempo máximo) y los fallos solo quedan en la bitácora de la inscripción; no reintenta.
