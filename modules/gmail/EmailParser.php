<?php

/**
 * EmailParser
 *
 * Extrae datos estructurados del body de un correo con formato TravelSoft.
 *
 * Formato esperado en el body:
 *   Name:        Juan García
 *   Email:       juan@gmail.com
 *   Phone:       +57 300 123 4567       (opcional)
 *   Destination: Italy - Rome           (requerido)
 *   Departure:   15/09/2026             (requerido, DD/MM/YYYY o YYYY-MM-DD)
 *   Return:      25/09/2026             (opcional)
 *   Travelers:   2                      (opcional, default 1)
 *   Budget:      5000                   (opcional)
 *   Notes:       ...                    (opcional, puede ser multilínea)
 *
 * Subject debe contener [TRAVELSOFT] para que el worker lo procese.
 */
class EmailParser {

    // Labels en inglés (case-insensitive)
    private const FIELD_MAP = [
        'name'        => 'nombre_cliente',
        'email'       => 'email_cliente',
        'phone'       => 'telefono_cliente',
        'destination' => 'destino',
        'departure'   => 'fecha_salida',
        'return'      => 'fecha_llegada',
        'travelers'   => 'viajeros',
        'budget'      => 'budget',
        'notes'       => 'descripcion',
    ];

    // Campos que deben estar presentes para crear el lead
    private const REQUIRED = ['nombre_cliente', 'email_cliente', 'destino', 'fecha_salida'];

    /**
     * Parsea el body crudo del correo.
     *
     * @param  string $rawBody   Body del email (HTML o texto plano)
     * @return array  [
     *   'data'   => [...],   // Campos extraídos
     *   'errors' => [...],   // Campos requeridos ausentes o inválidos
     *   'valid'  => bool     // true si tiene todos los campos requeridos y válidos
     * ]
     */
    public static function parse(string $rawBody): array {
        $text   = self::normalize($rawBody);
        $data   = self::extract($text);
        $errors = self::validate($data);

        return [
            'data'   => $data,
            'errors' => $errors,
            'valid'  => empty($errors),
        ];
    }

    // =========================================================
    // Normalización del body
    // =========================================================

    /**
     * Convierte el body a texto plano limpio, listo para parsear.
     */
    private static function normalize(string $raw): string {
        // 1. Decodificar entidades HTML
        $text = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 2. Reemplazar <br>, <br/>, <p>, <div> por salto de línea antes de strip_tags
        $text = preg_replace('/<br\s*\/?>/i',         "\n", $text);
        $text = preg_replace('/<\/?(p|div|li|tr)[^>]*>/i', "\n", $text);

        // 3. Eliminar todos los tags HTML restantes
        $text = strip_tags($text);

        // 4. Truncar en señales típicas de firma o mensaje citado
        //    Esto evita que el parser lea texto del forward o de la firma
        $stopPatterns = [
            '/^--\s*$/m',                      // firma estándar: línea con solo "--"
            '/^_{3,}$/m',                       // línea de guiones bajos: ___
            '/^-{3,}$/m',                       // línea de guiones: ---
            '/^On .+ wrote:$/m',                // Gmail forward en inglés
            '/^El .+ escribió:$/m',             // Gmail forward en español
            '/^From:\s+/m',                     // encabezado de forward
            '/^Sent from /im',                  // Sent from my iPhone / Outlook
            '/^Enviado desde /im',              // versión en español
        ];
        foreach ($stopPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                $text = substr($text, 0, $matches[0][1]);
            }
        }

        // 5. Normalizar espacios y saltos de línea
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);   // múltiples espacios/tabs → 1 espacio
        $text = preg_replace('/\n{3,}/', "\n\n", $text); // más de 2 saltos → 2

        return trim($text);
    }

    // =========================================================
    // Extracción de campos
    // =========================================================

    /**
     * Extrae todos los campos del texto normalizado.
     * Cada label se busca de forma case-insensitive con espacios opcionales.
     */
    private static function extract(string $text): array {
        $data  = [];
        $lines = explode("\n", $text);

        // Construimos un regex para cada label conocido
        // Formato: "Label : valor"  (espacios opcionales alrededor del ":")
        $labels  = array_keys(self::FIELD_MAP);
        $pattern = '/^(' . implode('|', array_map('preg_quote', $labels)) . ')\s*:\s*(.*)$/i';

        $currentField = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                $currentField = null;
                continue;
            }

            if (preg_match($pattern, $line, $m)) {
                $label        = strtolower($m[1]);
                $value        = trim($m[2]);
                $dbField      = self::FIELD_MAP[$label];
                $currentField = $dbField;

                // Notes puede ser multilínea: acumular
                if ($dbField === 'descripcion') {
                    $data[$dbField] = $value;
                } else {
                    $data[$dbField] = $value;
                }
            } elseif ($currentField === 'descripcion' && !empty($line)) {
                // Líneas adicionales de Notes se concatenan
                $data['descripcion'] = ($data['descripcion'] ?? '') . ' ' . $line;
            }
        }

        // Limpiar y castear campos
        return self::cast($data);
    }

    // =========================================================
    // Casteo y limpieza por tipo de campo
    // =========================================================

    private static function cast(array $data): array {

        // nombre_cliente: trim
        if (isset($data['nombre_cliente'])) {
            $data['nombre_cliente'] = trim($data['nombre_cliente']);
        }

        // email_cliente: lowercase + trim
        if (isset($data['email_cliente'])) {
            $data['email_cliente'] = strtolower(trim($data['email_cliente']));
        }

        // telefono_cliente: solo dígitos, +, -, espacios
        if (isset($data['telefono_cliente'])) {
            $data['telefono_cliente'] = trim($data['telefono_cliente']);
        }

        // destino: trim
        if (isset($data['destino'])) {
            $data['destino'] = trim($data['destino']);
        }

        // fecha_salida: normalizar a YYYY-MM-DD
        if (isset($data['fecha_salida'])) {
            $data['fecha_salida'] = self::parseDate($data['fecha_salida']);
        }

        // fecha_llegada: normalizar a YYYY-MM-DD
        if (isset($data['fecha_llegada'])) {
            $data['fecha_llegada'] = self::parseDate($data['fecha_llegada']);
        }

        // viajeros: entero >= 1
        if (isset($data['viajeros'])) {
            $v = (int) preg_replace('/[^0-9]/', '', $data['viajeros']);
            $data['viajeros'] = max(1, $v);
        }

        // budget: decimal positivo (strip $, comas, espacios)
        if (isset($data['budget'])) {
            $b = preg_replace('/[^0-9.]/', '', $data['budget']);
            $data['budget'] = $b !== '' ? (float) $b : null;
            if ($data['budget'] === 0.0) $data['budget'] = null;
        }

        // descripcion: trim
        if (isset($data['descripcion'])) {
            $data['descripcion'] = trim($data['descripcion']);
        }

        return $data;
    }

    // =========================================================
    // Parseo de fechas
    // =========================================================

    /**
     * Convierte una fecha en texto a formato YYYY-MM-DD.
     * Acepta: DD/MM/YYYY · DD-MM-YYYY · YYYY-MM-DD
     * Retorna null si no puede parsear.
     */
    private static function parseDate(string $raw): ?string {
        $raw = trim($raw);

        // DD/MM/YYYY o DD-MM-YYYY
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $raw, $m)) {
            $day   = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year  = $m[3];
            $date  = \DateTime::createFromFormat('Y-m-d', "$year-$month-$day");
            if ($date && $date->format('Y-m-d') === "$year-$month-$day") {
                return "$year-$month-$day";
            }
        }

        // YYYY-MM-DD (ISO)
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            $date = \DateTime::createFromFormat('Y-m-d', $raw);
            if ($date && $date->format('Y-m-d') === $raw) {
                return $raw;
            }
        }

        // Intentar con strtotime como último recurso
        $ts = strtotime($raw);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    // =========================================================
    // Validación
    // =========================================================

    /**
     * Verifica que todos los campos requeridos estén presentes y sean válidos.
     * Retorna array de strings con los errores encontrados.
     */
    private static function validate(array $data): array {
        $errors = [];

        foreach (self::REQUIRED as $field) {
            if (empty($data[$field])) {
                $label = array_search($field, self::FIELD_MAP);
                $errors[] = "Missing required field: $label ($field)";
            }
        }

        // Validar formato email
        if (!empty($data['email_cliente']) && !filter_var($data['email_cliente'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format: {$data['email_cliente']}";
        }

        // Validar fecha_salida parseada
        if (isset($data['fecha_salida']) && $data['fecha_salida'] === null) {
            $errors[] = "Invalid date format for Departure";
        }

        // Validar fecha_llegada si se envió
        if (array_key_exists('fecha_llegada', $data) && $data['fecha_llegada'] === null) {
            $errors[] = "Invalid date format for Return (ignored, set to null)";
            // No es bloqueante, solo advertencia — se deja null
        }

        return $errors;
    }
}
