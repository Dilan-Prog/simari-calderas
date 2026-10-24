<?php

/**
 * Tipos de notificación y el permiso (módulo, acción 'view' = "recibir") que da acceso a cada uno.
 * Se muestran en Roles bajo el grupo "Notificaciones" (una casilla por tipo).
 */
return [
    'permissions' => [
        'notif-lead-entrante'  => ['label' => 'Lead entrante',      'type' => 'lead_incoming'],
        'notif-lead-respuesta' => ['label' => 'Respuesta de lead',  'type' => 'lead_reply'],
        'notif-tarea'          => ['label' => 'Tareas',             'type' => 'task'],
    ],
];
