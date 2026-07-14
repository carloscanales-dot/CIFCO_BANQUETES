# Sistema de Impresión con Agente Local

## Resumen del Cambio

Se ha migrado el sistema de impresión directa (ePOS desde el navegador) a un sistema de cola de impresión donde:

1. **Frontend (Ventas.vue)**: Ahora solo guarda la transacción en el backend
2. **Backend (TransactionController)**: Crea un registro `PrintJob` con el payload de impresión
3. **Agente Local**: Consulta la API periódicamente, obtiene trabajos pendientes y ejecuta las impresiones localmente

## Archivos Creados/Modificados

### Backend - Nuevos Archivos

1. **Migración de agentes**: `Modules/Caja/database/migrations/2026_01_27_100000_create_print_agents_table.php`
2. **Modelo PrintAgent**: `Modules/Caja/Models/PrintAgent.php`
3. **Middleware de autenticación**: `Modules/Caja/Http/Middleware/AuthenticatePrintAgent.php`
4. **Controlador de API**: `Modules/Caja/Http/Controllers/AgentPrintJobController.php`
5. **Seeder**: `Modules/Caja/database/seeders/PrintAgentSeeder.php`

### Backend - Archivos Modificados

1. **routes/api.php**: Rutas nuevas para el agente
2. **TransactionController.php**: Crea PrintJobs al guardar transacciones
3. **Ventas.vue**: Eliminada lógica de impresión ePOS

## Pasos de Instalación

### 1. Ejecutar Migraciones

```bash
php artisan migrate
```

Esto creará las tablas:
- `print_agents`: Agentes autorizados para imprimir
- `print_jobs`: Ya existía, contiene los trabajos de impresión

### 2. Crear Agente de Prueba

```bash
php artisan db:seed --class=Modules\\Caja\\Database\\Seeders\\PrintAgentSeeder
```

Esto creará un agente con:
- **Agent ID**: `LOCAL_AGENT_001`
- **Secret**: `secret123`

### 3. Configurar el Agente Local

En tu proyecto del agente local, actualiza el `.env`:

```env
LARAVEL_BASE_URL=https://bistro.cifco.gob.sv
AGENT_ID=LOCAL_AGENT_001
AGENT_SECRET=secret123
POLL_INTERVAL=5000
```

### 4. Endpoints de la API

#### Login
```
POST /api/agent/login
Body: {
  "agent_id": "LOCAL_AGENT_001",
  "agent_secret": "secret123"
}
Response: {
  "token": "LOCAL_AGENT_001",
  "agent": { "id": 1, "name": "...", "location": "..." }
}
```

#### Obtener Trabajos Pendientes
```
GET /api/agent/print-jobs/pending
Headers: Authorization: Bearer LOCAL_AGENT_001
Response: {
  "jobs": [
    {
      "id": 1,
      "type": "sale",
      "payload": { ... },
      "transaction_id": 123,
      "station_name": "Caja 1",
      "cashier_name": "Juan Pérez",
      "created_at": "2026-01-27T10:30:00Z"
    }
  ]
}
```

#### Marcar como Impreso
```
POST /api/agent/print-jobs/{id}/printed
Headers: Authorization: Bearer LOCAL_AGENT_001
```

#### Marcar como Fallido
```
POST /api/agent/print-jobs/{id}/failed
Headers: Authorization: Bearer LOCAL_AGENT_001
Body: {
  "error": "Error de conexión con impresora"
}
```

## Estructura del Payload

El payload que recibe el agente tiene esta estructura:

```javascript
{
  "printer": {
    "ip": "192.168.1.100",
    "port": 9100
  },
  "fair_name": "CIFCO 2026",
  "transaction_id": 12345,
  "station_name": "Caja 1",
  "cashier_name": "Juan Pérez",
  "payment_method": 1,  // 1=Efectivo, 2=Tarjeta, 3=Chivo
  "cash_amount": 30.00,
  "total": 28.75,
  "items": [
    {
      "product_name": "Hamburguesa",
      "quantity": 2,
      "unit_price": 5.50
    }
  ]
}
```

## Flujo Completo

1. Usuario selecciona productos en **Ventas.vue**
2. Presiona "Pagar" y selecciona método de pago
3. Frontend hace POST a `/ticket/cajas/transactions/store`
4. Backend guarda la transacción Y crea un `PrintJob` con status='pending'
5. Usuario ve mensaje: "Venta registrada. El ticket se imprimirá automáticamente."
6. Agente local consulta `/api/agent/print-jobs/pending` cada X segundos
7. Agente obtiene el job, imprime el ticket
8. Agente marca el job como 'printed' con POST a `/api/agent/print-jobs/{id}/printed`

## Próximos Pasos

- [ ] Implementar impresión de pre-cierre en el agente (tipo: 'preclose')
- [ ] Implementar impresión de venta de empleado (tipo: 'employee_sale')
- [ ] Agregar retry automático para jobs fallidos
- [ ] Dashboard para monitorear estado de impresiones
- [ ] Notificaciones en tiempo real cuando un job falla

## Troubleshooting

### El agente no puede autenticarse
- Verifica que el seeder se haya ejecutado correctamente
- Verifica que `AGENT_ID` y `AGENT_SECRET` en el `.env` del agente coincidan

### Los jobs no se crean
- Verifica que la estación tenga una impresora asociada
- Revisa los logs de Laravel: `storage/logs/laravel.log`

### El payload no tiene IP de impresora
- Verifica que el modelo `Station` tenga relación con `Printer`
- Asegúrate de que la estación tenga una impresora asignada en la BD

## Notas Adicionales

- El sistema ya NO usa impresión directa desde el navegador (ePOS)
- Todos los botones de "Reconectar Impresora" fueron eliminados
- La función `printReceipt` fue reemplazada por `saveTransaction`
- El pre-cierre ya no imprime directamente, deberás implementarlo en el agente
