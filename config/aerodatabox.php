<?php
// =====================================
// ARCHIVO: config/aerodatabox.php
// Configuración de AeroDataBox API via RapidAPI
// =====================================
// RECOMENDACIÓN: Agrega esta línea a tu archivo .env
//   RAPIDAPI_KEY=4fd60f6e09msh1088cef1d874ac8p18e81fjsn9d923ff56935
// =====================================

class AeroDataBox {

    const API_KEY  = '4fd60f6e09msh1088cef1d874ac8p18e81fjsn9d923ff56935';
    const API_HOST = 'aerodatabox.p.rapidapi.com';
    const BASE_URL = 'https://aerodatabox.p.rapidapi.com';

    /**
     * Devuelve la API Key (prioriza .env sobre la constante)
     */
    public static function getApiKey(): string {
        return $_ENV['RAPIDAPI_KEY'] ?? self::API_KEY;
    }

    /**
     * Headers estándar para cada request a RapidAPI
     */
    public static function getHeaders(): array {
        return [
            'X-RapidAPI-Key: '  . self::getApiKey(),
            'X-RapidAPI-Host: ' . self::API_HOST,
            'Accept: application/json',
        ];
    }

    /**
     * URL del endpoint de búsqueda por número de vuelo y fecha
     * Endpoint: GET /flights/number/{flightNumber}/{date}
     *
     * @param string $flightNumber  Código IATA (ej: EK521)
     * @param string $date          Fecha YYYY-MM-DD
     */
    public static function buildFlightUrl(string $flightNumber, string $date): string {
        $number = strtoupper(trim($flightNumber));
        return self::BASE_URL . "/flights/number/{$number}/{$date}";
    }

    /**
     * Llama a AeroDataBox y devuelve los datos del vuelo mapeados a codigos_vuelos.
     * Devuelve null si el vuelo no existe o la API falla.
     *
     * @param string $flightNumber  Código IATA (ej: EK384)
     * @param string $date          Fecha YYYY-MM-DD
     */
    public static function fetchFlight(string $flightNumber, string $date): ?array {
        // --- SECCIÓN 1: Ejecutar el request HTTP ---
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => self::buildFlightUrl($flightNumber, $date),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => self::getHeaders(),
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // --- SECCIÓN 2: Validar la respuesta ---
        if ($response === false || $httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);

        if (empty($data) || !isset($data[0])) {
            return null;
        }

        // --- SECCIÓN 3: Mapear la respuesta a los campos de codigos_vuelos ---
        $flight = $data[0];

        return [
            'codigo_vuelo'              => strtoupper(trim($flightNumber)),
            'aerolinea'                 => $flight['airline']['name']                          ?? '',
            'ciudad_origen'             => $flight['departure']['airport']['municipalityName'] ?? '',
            'codigo_aeropuerto_origen'  => $flight['departure']['airport']['iata']             ?? '',
            'aeropuerto_origen'         => $flight['departure']['airport']['name']             ?? '',
            'ciudad_destino'            => $flight['arrival']['airport']['municipalityName']   ?? '',
            'codigo_aeropuerto_destino' => $flight['arrival']['airport']['iata']               ?? '',
            'aeropuerto_destino'        => $flight['arrival']['airport']['name']               ?? '',
            'terminal'                  => $flight['departure']['terminal']         ?? null,
            'hora_salida'               => substr($flight['departure']['scheduledTime']['local'] ?? '', 11, 5),
            'hora_llegada'              => substr($flight['arrival']['scheduledTime']['local']   ?? '', 11, 5),
        ];
    }
}
