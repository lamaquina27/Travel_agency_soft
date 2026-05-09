# Fix: Ocultar campo de acomodación vacío en itinerary

**Fecha:** 2026-05-08  
**Branch:** `kevin`  
**Archivos modificados:** 1

---

## El problema

En la vista pública del itinerario (`/itinerary`), cuando un servicio de tipo `alojamiento` no tenía acomodación definida, se mostraba un mensaje de texto visible al cliente:

```
🛏 Acomodación por definir
```

Este mensaje aparecía en estilo `muted` (gris) pero seguía siendo visible. El comportamiento esperado es que si no hay acomodación registrada, el campo simplemente no se muestre.

---

## Causa

En `pages/itinerary.php`, el bloque que renderiza los servicios de alojamiento tenía una estructura `if / elseif`:

```php
// Si tiene acomodación → la muestra
<?php if ($servicio['tipo_servicio'] === 'alojamiento' && !empty($servicio['acomodacion_nombre'])): ?>
    <div class="accommodation-detail">
        <i class="fas fa-bed"></i>
        <span>
            <?= htmlspecialchars($servicio['acomodacion_nombre']) ?>
            ...
        </span>
    </div>

// Si NO tiene acomodación → mostraba el placeholder
<?php elseif ($servicio['tipo_servicio'] === 'alojamiento'): ?>
    <div class="accommodation-detail muted">
        <i class="fas fa-bed"></i>
        <span>Acomodación por definir</span>   ← se eliminó
    </div>

<?php endif; ?>
```

---

## Solución aplicada

Se eliminó el bloque `elseif` completo. El bloque `if` que muestra la acomodación real queda intacto.

```php
// DESPUÉS: solo se renderiza si hay datos
<?php if ($servicio['tipo_servicio'] === 'alojamiento' && !empty($servicio['acomodacion_nombre'])): ?>
    <div class="accommodation-detail">
        <i class="fas fa-bed"></i>
        <span>
            <?= htmlspecialchars($servicio['acomodacion_nombre']) ?>
            ...
        </span>
    </div>
<?php endif; ?>
```

---

## Alcance de la búsqueda

Se verificó que el texto `"Acomodación por definir"` **solo existía en `itinerary.php`**. No aparece en `preview.php`, ni en ningún módulo o API del proyecto.

---

## Pendiente futuro — `preview.php`

`pages/preview.php` actualmente no renderiza acomodaciones en el detalle de servicios, por lo que este cambio no la afecta.

> **Si en el futuro se agrega la sección de acomodaciones a `preview.php`, aplicar el mismo criterio: no mostrar ningún placeholder cuando la acomodación esté vacía.** El campo debe ser invisible al cliente hasta que el agente haya registrado los datos de acomodación.

---

## Resumen de archivos

| Archivo | Cambio |
|---|---|
| `pages/itinerary.php` | Eliminado bloque `elseif` que mostraba "Acomodación por definir" |
