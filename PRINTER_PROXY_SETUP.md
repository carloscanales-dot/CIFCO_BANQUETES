# Configuración de Proxy HTTPS para Impresora Epson ePOS

## Problema Original
En producción (HTTPS), el navegador bloqueaba las solicitudes HTTP a la impresora por **Mixed Content** (contenido mixto).

## Solución Implementada
Se creó un proxy HTTPS en el servidor Laravel que actúa como intermediario entre el cliente (navegador) y la impresora.

## Cambios Realizados

### 1. Backend (Laravel)

**Archivo:** `app/Http/Controllers/PrinterController.php`
- Agregado método `proxyRequest()` que:
  - Recibe solicitudes POST desde el cliente con `printer_ip` y `path`
  - Realiza la solicitud HTTP a la impresora de forma segura en el servidor
  - Devuelve la respuesta al cliente

**Archivo:** `routes/web.php`
- Agregada ruta: `POST /printer-proxy` (con middleware de autenticación)
- Accesible solo para usuarios autenticados

### 2. Frontend (Vue.js - Ventas.vue)

**Cambios principales:**

1. **Nueva función `connectPrinterWithProxy()`:**
   - Detecta si está en producción (HTTPS) o desarrollo (HTTP)
   - En producción: conecta a través del proxy del servidor
   - En desarrollo: conecta directamente a la impresora (como antes)

2. **Actualizado `performFullReconnect()`:**
   - Ahora es asíncrona y usa `connectPrinterWithProxy()`
   - Manejo de errores mejorado

3. **Actualizado `onMounted()`:**
   - Usa `connectPrinterWithProxy()` al cargar la página

## Cómo Funciona

### En Desarrollo (HTTP://localhost)
```
Cliente → Impresora (10.0.0.100:8008)
```
Conexión directa, sin cambios.

### En Producción (HTTPS://bistro.cifco.gob.sv)
```
Cliente → Servidor Laravel (HTTPS) → Proxy → Impresora (HTTP)
```
1. Cliente solicita conexión a impresora
2. JavaScript detecta HTTPS y usa `connectPrinterWithProxy()`
3. Laravel proxy recibe la solicitud y se conecta a la impresora
4. Laravel devuelve la respuesta al cliente (HTTPS)
5. Sin errores de contenido mixto

## Requisitos

- ✅ Laravel HTTP client (incluido en Laravel)
- ✅ Usuario autenticado para acceder al proxy
- ✅ La impresora debe estar accesible desde el servidor

## Testing

Para probar en producción:

1. Abre la consola del navegador (F12)
2. Ve a la pestaña "Ventas"
3. Haz clic en "Reconectar Impresora"
4. Verifica que no haya errores de "Mixed Content"
5. La impresora debería reconectarse exitosamente

## Consideraciones de Seguridad

- ✅ Solo usuarios autenticados pueden acceder al proxy
- ✅ Las solicitudes pasan a través del servidor (más seguro)
- ✅ Se pueden agregar validaciones adicionales si es necesario
- ⚠️ Asegúrate de que la impresora esté en una red privada

## Rollback (Si es necesario)

Si necesitas volver a la conexión directa:
1. Comentar los cambios en `Ventas.vue`
2. Restaurar el código original de `epos.connect()`
3. Eliminar la ruta de proxy en `routes/web.php`

## Notas
- La función `connectPrinterWithProxy()` retorna una Promise con `{ printer, epos }`
- El proxy está disponible en: `POST /printer-proxy`
- Tiempo de timeout: 10 segundos (configurable en `PrinterController`)
