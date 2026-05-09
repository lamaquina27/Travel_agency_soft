# Fix: Conteo de viajeros siempre mostraba "1 viajero"

**Fecha:** 2026-05-08  
**Branch:** `kevin`  
**Archivos modificados:** 4

---

## El problema

En tres vistas del sistema — **Itinerarios** (lista), **Preview** (propuesta al cliente) e **Itinerary** (itinerario público) — el contador de viajeros siempre mostraba `1 viajero` sin importar cuántos viajeros tuviera asociado el programa.

---

## Causa raíz

### Contexto del modelo de datos

El sistema maneja el conteo de viajeros en dos lugares distintos:

1. **`programa_solicitudes.numero_pasajeros`** — campo numérico que se guarda al crear/editar el programa desde el formulario de `programa.php`.
2. **`viajeros_solicitud`** — tabla pivote que vincula perfiles de viajeros reales (`viajeros`) con programas (`programa_solicitudes`).

### El bug: `numero_pasajeros` siempre se guardaba como `1`

El formulario de `programa.php` **nunca tuvo un `<input name="passengers">`** en el HTML. Sin embargo, el API (`modules/programa/api.php`) esperaba ese campo del `$_POST`:

```php
// modules/programa/api.php — tanto en create como en update
'numero_pasajeros' => intval($_POST['passengers'] ?? 1),
```

Como `$_POST['passengers']` nunca llegaba, el fallback `?? 1` siempre ganaba. Todos los programas — nuevos y editados — quedaban con `numero_pasajeros = 1` en la base de datos.

Hay un intento de corrección en `programa.php` (línea ~9754) que busca el elemento y lo actualiza desde la sección de precios:

```javascript
const passengersInput = document.getElementById('passengers');
if (passengersInput && totalPasajeros > 0) {
    passengersInput.value = totalPasajeros;  // passengersInput es null, nunca ejecuta
}
```

Pero como el elemento no existe en el DOM, `passengersInput` es siempre `null` y la asignación nunca ocurre.

### Por qué las vistas mostraban ese valor

Las tres vistas afectadas leían directamente `numero_pasajeros`:

| Vista | Archivo | Código |
|---|---|---|
| Lista de itinerarios | `pages/itinerarios.php` | `${programa.numero_pasajeros}` (JS) |
| Vista pública del itinerario | `pages/itinerary.php` | `$programa['numero_pasajeros']` (PHP) |
| Preview de propuesta | `pages/preview.php` | `$programa['numero_pasajeros']` (PHP) |

---

## Solución aplicada

Se decidió leer el conteo **desde `viajeros_solicitud`** en lugar de `numero_pasajeros`, porque:

- Es la fuente autoritativa: cada vez que el agente vincula un viajero al programa, se inserta una fila en esa tabla.
- Corrige automáticamente todos los programas ya existentes en BD (no requiere migración de datos).
- No depende de que el formulario guarde el campo correctamente.

### Cambio 1 — `modules/programa/api.php`

En la acción `list_all` (usada por `itinerarios.php` vía AJAX), se agregó una subquery al `SELECT`:

```sql
-- ANTES
SELECT ps.*,
       u.full_name as created_by_name,
       pp.titulo_programa,
       pp.foto_portada,
       pp.idioma_predeterminado,
       (SELECT COALESCE(SUM(pd.duracion_estancia), COUNT(pd.id))
        FROM programa_dias pd
        WHERE pd.solicitud_id = ps.id) as total_dias_real
FROM programa_solicitudes ps ...

-- DESPUÉS (se agrega)
       (SELECT COUNT(*)
        FROM viajeros_solicitud vs
        WHERE vs.solicitud_id = ps.id) as viajeros_count
```

### Cambio 2 — `pages/itinerarios.php`

En el template de tarjeta JS, se reemplaza la fuente del dato:

```javascript
// ANTES
<div class="detail-value">${programa.numero_pasajeros}</div>

// DESPUÉS
<div class="detail-value">${programa.viajeros_count}</div>
```

### Cambio 3 — `pages/itinerary.php`

Se agrega la subquery al `SELECT` de carga del programa:

```sql
-- Se agrega al SELECT existente:
(SELECT COUNT(*) FROM viajeros_solicitud vs WHERE vs.solicitud_id = ps.id) as viajeros_count
```

Y se cambia la asignación de la variable PHP:

```php
// ANTES
$num_pasajeros = $programa['numero_pasajeros'];

// DESPUÉS
$num_pasajeros = (int) ($programa['viajeros_count'] ?? $programa['numero_pasajeros'] ?? 1);
if ($num_pasajeros <= 0) $num_pasajeros = 1;
```

El fallback en cascada (`?? $programa['numero_pasajeros'] ?? 1`) garantiza compatibilidad si por alguna razón la subquery no devolviera el campo.

### Cambio 4 — `pages/preview.php`

Mismo patrón que `itinerary.php`:

```sql
-- Se agrega al SELECT existente:
(SELECT COUNT(*) FROM viajeros_solicitud vs WHERE vs.solicitud_id = ps.id) as viajeros_count
```

```php
// ANTES
$num_pasajeros = (int) ($programa['numero_pasajeros'] ?? 1);

// DESPUÉS
$num_pasajeros = (int) ($programa['viajeros_count'] ?? $programa['numero_pasajeros'] ?? 1);
```

---

## Deuda técnica pendiente

El campo `numero_pasajeros` en `programa_solicitudes` sigue sin guardarse correctamente porque el formulario de `programa.php` no tiene el `<input name="passengers">`. Esto no afecta el display (ya resuelto), pero sí significa que el campo en BD no refleja la realidad.

**Recomendación futura:** agregar un `<input type="hidden" id="passengers" name="passengers">` al formulario y poblarlo con `viajerosSeleccionados.length` justo antes del submit, para mantener `numero_pasajeros` sincronizado con `viajeros_count`.

---

## Resumen de archivos

| Archivo | Tipo de cambio |
|---|---|
| `modules/programa/api.php` | Subquery `viajeros_count` en `list_all` |
| `pages/itinerarios.php` | Template JS: `viajeros_count` en lugar de `numero_pasajeros` |
| `pages/itinerary.php` | Subquery + nueva asignación de `$num_pasajeros` |
| `pages/preview.php` | Subquery + nueva asignación de `$num_pasajeros` |
