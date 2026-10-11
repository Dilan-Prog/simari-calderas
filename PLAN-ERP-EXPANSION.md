# Plan de expansión ERP — Equiterm Industries

Plan único por fases para los módulos que aún no existen: Proyectos, BOM, MRP, Finanzas, Logística, Producción, Almacén y CRM propio. Diseño visual inspirado en SAP (Fiori / S/4HANA).

> Estado: planeación (oct 2026). Ninguna fase está construida todavía.

## 1. Decisiones tomadas

| Tema | Decisión |
|---|---|
| CRM | Vive 100 % dentro de nuestro sistema. **No se integra Kommo ni n8n.** El FigJam "Diagrama de Flujo Kommo" solo se usa como referencia de flujos (captura de leads, atribución, conversiones). |
| Cuentas por cobrar / pagar | Solo **gestión interna**: sin contabilidad general ni timbrado CFDI. |
| Producción | Se hacen **ambas** cosas: fabricación propia (requiere BOM) y servicio/instalación. El MRP cubre los dos orígenes de demanda. |
| Diseño | Estilo SAP Fiori (launchpad, object page, document flow), generado con Claude Design. |

## 2. Qué existe y qué falta

| Área | Ya construido | Falta |
|---|---|---|
| Ventas | Pipelines, Negocios, Tareas, Cotizaciones, Clientes, WhatsApp, Automatizaciones, Email Marketing | Bandeja unificada de leads, historial único por cliente |
| Atribución / Google Ads | Captura propia `AdVisit`/`AdEvent`, panel de conversiones | Envío real a Google (hoy es un stub), control anti-duplicados, reintentos, ajustes |
| Finanzas | Órdenes de Compra, Proveedores, métodos de pago, pagos de tienda (Mercado Pago) | Cuentas por cobrar, por pagar, pagos y aplicaciones, antigüedad de saldos, conciliación |
| Logística | Envíos (solo pedidos de tienda), Entrega de Material (documental, con firma) | Envíos B2B, Recolecciones |
| Producción | Servicios técnicos, Reportes de servicio | Calendario de servicios, órdenes de producción, fechas límite |
| Planeación / MRP | Planeación de químicos (solo químicos) | Proyectos con fases, BOM, MRP completo, reservas |
| Almacén | Inventario por almacén (entradas, salidas, ajustes) | Traspasos, conteo físico, ubicaciones, reservas |

## 3. Fases

### Fase 0 — Base compartida
Todo lo demás depende de esta fase.

- **Permisos** por módulo nuevo (`projects`, `boms`, `mrp`, `receivables`, `payables`, `pickups`, `production`) con el mismo esquema que los actuales.
- **Document Flow:** tabla genérica `document_links` (tipo y id de origen, tipo y id de destino). Alimenta el panel "Flujo del documento":
  Cotización → Pedido → Proyecto → Requerimiento → Orden de compra → Entrada de almacén → Envío → Cuenta por cobrar → Pago.
  Se alimenta desde los módulos actuales (Cotización → Pedido ya existe).
- **Lanzador (launchpad) por rol:** inicio con tiles agrupados por módulo y KPIs en vivo con semáforo. Se alimenta del módulo de Estadísticas existente.
- **Barra de comandos Ctrl+K** para saltar a cualquier módulo o documento por folio.
- **Componentes Blade:** `document-flow`, `object-header` (cabecera con semáforo de estatus), `process-chevrons`.

### Fase 1 — Planeación y Proyectos
- **Modelos:** `Project` (folio `PRY-AAAA-NNNN`, nombre, descripción, cliente, responsable, tiempo estimado, fecha límite, estado), `ProjectPhase` (nombre, encargados, requisitos, descripción, orden, avance, fechas), `ProjectPhaseAssignee`.
- **Estados:** Levantamiento, Planeado, En ejecución, Pausado, Cerrado.
- **Vínculos:** `project_id` en `quotes`, `technical_services` y `service_reports`; visible en el Document Flow.
- **Pantallas:** listado con filtros y chips, vista de objeto con pestañas (General, Fases, Cotizaciones, Servicios, Materiales, Historial), línea de tiempo tipo Gantt, tablero de estado, semáforo de fecha límite.
- **Automatización:** al aceptar una cotización con proyecto, el pedido se crea y se enlaza al proyecto (reutiliza el auto-split existente).

### Fase 2A — Lista de materiales (BOM)
- **Modelos:** `Bom` (producto fabricado, versión, vigente sí/no) y `BomLine` (componente, cantidad, unidad, merma %).
- **Multinivel:** un componente puede ser un subensamble con su propia BOM.
- **Costeo estándar:** suma de componentes con el costo del producto (o el último precio del proveedor).
- **Pantalla:** pestaña "Lista de materiales" en el producto fabricado, vista de árbol, duplicar versión.

### Fase 2 — MRP y reservas
- **Modelos:** `MaterialRequirement`, `MaterialRequirementLine` (producto, requerido, existencia, reservado, faltante, origen), `StockReservation` (producto, almacén, proyecto, cantidad).
- **Dos orígenes de demanda:**
  - *Fabricación:* pedido o proyecto con producto fabricado → se explota la BOM → componentes requeridos.
  - *Servicio e instalación:* materiales planeados del servicio (`ServiceMaterialPlanned`), líneas de cotización con productos y químicos proyectados.
- **Cálculo:** requerido − (existencia − reservas) = faltante.
- **Sugerencias de compra** agrupadas por proveedor con el precio de `SupplierProduct`. El botón "Generar orden de compra" crea la `PurchaseOrder` y la liga en el Document Flow. Al aceptarse la orden, la entrada de almacén ya funciona con `InventoryService`.
- **Pantalla:** cadena visible Planeación → Compras → Finanzas → Logística.

### Fase 3 — Finanzas (gestión interna)
- **`Receivable` (cuentas por cobrar):** cliente, documento origen, monto, vencimiento, saldo, estatus. Se genera desde pedido o proyecto con condiciones de crédito. Antigüedad de saldos (0-30 / 31-60 / 61-90 / +90) y estado de cuenta por cliente.
- **`Payable` (cuentas por pagar):** se genera desde la `PurchaseOrder` aceptada o recibida.
- **`Payment` y `PaymentApplication`:** un pago se aplica a uno o varios documentos; método, referencia, comprobante, moneda y tipo de cambio. Conciliación simple: pendiente, aplicado, conciliado.
- **Reuso:** `PaymentMethod` y la lógica MXN/USD de las cotizaciones.
- **Panel:** por cobrar, por pagar, vencido, flujo a 30 días.
- **Fuera de alcance:** contabilidad general y timbrado CFDI.

### Fase 4 — Logística
- **Envíos B2B:** `Shipment` hoy solo cuelga de `StoreOrder`. Se extiende con un `shippable` (pedido de tienda, pedido B2B o cotización). Campos: guía, cliente, número de pedido, quién y qué empresa recibe, dirección de salida y de entrega, fecha de envío, teléfono, correo, cotización de referencia.
- **Recolecciones:** `Pickup` y `PickupItem` (pedidos, material o cualquier cosa por recolectar o enviar). Programación, chofer o paquetería, estatus, evidencia con foto y firma en móvil (patrón de Entrega de Material).
- **Vista "ruta del día"** para logística.
- **Entrega de Material** se mantiene; se liga a Envío y a Proyecto en el Document Flow.

### Fase 5 — Producción
- **Calendario de servicios:** vista mes/semana/día sobre `technical_services`, por técnico y por proyecto, con arrastrar para reprogramar.
- **`ProductionOrder`:** proyecto o pedido, estatus de fabricación, fecha límite, avance. Al ejecutarse:
  - reserva los componentes de la BOM;
  - descuenta componentes del almacén al consumirlos;
  - da entrada al producto terminado;
  - registra costo real contra costo estándar.
- **Alertas** por retraso mediante las Automatizaciones y Tareas existentes.
- Conectada con Planeación, Cotización y Reportes de servicio.

### Fase 6 — Almacén (ampliaciones)
- Traspaso entre almacenes (dos movimientos ligados por `InventoryService`).
- Conteo físico con diferencias que generan ajustes.
- Ubicaciones; reservas visibles en Inventario.

### Fase 7 — CRM propio y conversiones a Google
Construido sobre Pipelines, Negocios, WhatsApp y `AdVisit`/`AdEvent` existentes.

- **Bandeja unificada de leads:** WhatsApp, formularios del sitio y llamadas (registradas a mano o por webhook), con origen y atribución por lead.
- **Contacto existente:** detección por teléfono o correo (teléfonos normalizados a +52). Si existe, se actualiza su lead abierto: el último clic se guarda y se conserva el primero.
- **Llamada perdida:** crea una tarea de seguimiento.
- **Conversiones a Google Ads con la Data Manager API** (la API `UploadClickConversions` ya no acepta usuarios nuevos desde junio de 2026):
  - dispara al pasar el negocio a Cotizado (secundaria) o Venta ganada (principal, con monto en MXN);
  - tabla de control anti-duplicados (lead, acción, fecha, `order_id`);
  - identificador: GCLID, GBRAID/WBRAID, o correo y teléfono con hash SHA-256;
  - reintentos con espera y alerta tras 3 fallos;
  - ajustes por cambio de monto (RESTATEMENT) y por venta cancelada (RETRACTION);
  - respetar la ventana de 90 días del GCLID.
- **Fuera de alcance:** call tracking con número dinámico (depende de un proveedor externo; solo queda como campo de registro).

## 4. Orden y dependencias

| Orden | Fase | Depende de |
|---|---|---|
| 1 | 0 Base | — |
| 2 | 1 Proyectos | 0 |
| 3 | 2A BOM | 0 |
| 4 | 2 MRP y reservas | 1, 2A |
| 5 | 3 Finanzas | 2 (órdenes de compra) |
| 6 | 4 Logística | 1 |
| 7 | 5 Producción | 1, 2A, 2 |
| 8 | 6 Almacén | 2 |
| 9 | 7 CRM propio | independiente |

Las fases 4 y 5 pueden ir en paralelo si hay varias sesiones.

## 5. Transversal a todas las fases
- Controladores delgados, servicios por módulo (patrón de `QuoteService`), auditoría con `LogsActivity`, folios con el mismo patrón que los demás módulos.
- Cada fase con pruebas de funcionalidad y de servicios.
- Al cerrar cada fase se actualiza la bóveda de Obsidian.
- Migraciones en producción por fase, en commits separados; el compilado `public/build` se versiona junto con el código.

## 6. Anexo — Prompt para Claude Design

```
Diseña los módulos nuevos del ERP de Equiterm Industries (calderas, equipos térmicos y servicios
industriales en México). Ya existe un panel admin en Laravel/Blade con módulos de Ventas (Pipelines,
Negocios, Tareas, Cotizaciones, Clientes), Pedidos, Compras, Inventario, Servicios Técnicos y Reportes.
Tú diseñas SOLO lo que falta, y debe sentirse parte del mismo sistema.

INSPIRACIÓN: SAP (Fiori / S/4HANA), pero más limpio y ligero:
1. Launchpad: inicio por rol con "tiles" agrupados por módulo (Ventas, Planeación/MRP, Compras,
   Almacén, Producción, Logística, Finanzas). Cada tile muestra un KPI vivo con semáforo.
2. Document Flow: en toda pantalla de documento, un panel "Flujo del documento" con la cadena
   Cotización → Pedido → Proyecto → Requerimiento de material → Orden de compra → Entrada de almacén →
   Envío/Entrega → Cuenta por cobrar → Pago. Cada nodo es clicable y muestra estatus.
3. Object Page: cabecera con título, estatus (semáforo), 3-4 datos clave y acciones principales;
   debajo, pestañas (General, Partidas, Documentos, Historial, Notas).
4. Listados: tabla densa con filtros avanzados y chips de filtros activos, vistas guardadas,
   columnas configurables, selección múltiple y acciones en lote.
5. Barra de comandos Ctrl+K para saltar a cualquier módulo o documento por folio.
6. Process flow: tira de chevrons para ciclos completos (Order-to-Cash, Procure-to-Pay,
   Plan-to-Produce) indicando en qué paso está cada documento.

ESTILO VISUAL: fondo claro, naranja Equiterm (#f97316) como acento, grises neutros, semáforos
verde/ámbar/rojo, tipografía sans legible, tablas compactas, tarjetas con bordes suaves.
Escritorio primero; las pantallas de campo (recolecciones, entregas) también en móvil.
Todo en español de México. Moneda MXN/USD, IVA, folios tipo COT-2026-0001.

MÓDULOS (cada uno con: listado, vista de objeto, formulario de alta/edición, estados vacío/error):

A) PLANEACIÓN / PROYECTOS: levantamiento (nombre, descripción, cliente y empresa, persona a cargo,
   tiempo aproximado, estado); asociar cotizaciones, reportes de servicio y servicios programados;
   N fases (nombre, encargados, requisitos, descripción) con Gantt y avance; tablero de estado con
   semáforo de fecha límite.
B) BOM Y MRP: árbol de lista de materiales multinivel por producto fabricado (versiones, merma,
   costo estándar); requerimiento de materiales por proyecto/pedido contra existencias y reservas;
   sugerencias de compra por proveedor con botón "Generar orden de compra"; cadena Planeación →
   Compras → Finanzas → Logística.
C) PRODUCCIÓN: calendario de servicios (mes/semana/día, por técnico y proyecto, arrastrar para
   reprogramar); orden de producción (componentes reservados/consumidos, producto terminado, costo
   real vs estándar); estatus de fabricación y deadline con alertas por retraso.
D) FINANZAS (gestión interna): cuentas por cobrar con antigüedad de saldos y estado de cuenta;
   cuentas por pagar ligadas a órdenes de compra; registro y aplicación de pagos a uno o varios
   documentos; panel con KPIs (por cobrar, por pagar, vencido, flujo de 30 días).
E) LOGÍSTICA: envíos (guía, cliente, número de pedido, persona y empresa que recibe, dirección de
   salida y de entrega, fecha de envío, teléfono, correo, cotización de referencia) con línea de
   tiempo de estatus; recolecciones (pedidos, material o cualquier cosa por recolectar/enviar) con
   evidencia de foto y firma en móvil; ruta del día.
F) ALMACÉN (ampliaciones): traspaso entre almacenes, conteo físico con diferencias, reservas.
G) CRM PROPIO: bandeja unificada de leads (WhatsApp, formulario, llamada) con origen/atribución
   y detección de contacto existente; pantalla de conversiones a Google Ads (pendiente, enviada,
   fallida, omitida por duplicado, sin atribución) con reintentos.

ENTREGABLES: 1) launchpad por rol; 2) patrón reutilizable de Object Page + Document Flow;
3) pantallas de cada módulo A-G; 4) versión móvil de Recolecciones/Envíos; 5) guía de componentes
(tiles, chips de estatus, tira de proceso). Prioriza consistencia entre módulos sobre cantidad.
```
