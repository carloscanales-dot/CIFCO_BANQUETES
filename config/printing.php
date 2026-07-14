<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Sistema de Impresión
    |--------------------------------------------------------------------------
    |
    | Configuración para el sistema de cola de impresión con agente local
    |
    */

    // Puerto por defecto de impresoras térmicas
    'default_printer_port' => env('PRINTER_DEFAULT_PORT', 9100),

    // Tiempo de vida de jobs completados antes de ser limpiados (días)
    'job_retention_days' => env('PRINT_JOB_RETENTION_DAYS', 30),

    // Máximo de reintentos para jobs fallidos
    'max_retries' => env('PRINT_JOB_MAX_RETRIES', 3),

    // Tipos de jobs disponibles
    'job_types' => [
        'sale' => 'Venta Normal',
        'employee_sale' => 'Venta Empleado',
        'preclose' => 'Pre-cierre',
        'close' => 'Cierre de Caja',
        'reprint' => 'Re-impresión',
    ],

    // Estados de jobs
    'statuses' => [
        'pending' => 'Pendiente',
        'processing' => 'Procesando',
        'printed' => 'Impreso',
        'failed' => 'Fallido',
    ],

    // Configuración de impresión
    'printing' => [
        'ticket_width' => 48, // Ancho en caracteres para impresoras de 80mm
        'logo_enabled' => env('PRINT_LOGO_ENABLED', true),
        'barcode_enabled' => env('PRINT_BARCODE_ENABLED', false),
        'duplicate_receipt' => env('PRINT_DUPLICATE', true), // Imprimir por duplicado
    ],
];
