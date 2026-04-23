<?php
// =====================================
// ARCHIVO: config/functions.php - Funciones Globales del Sistema
// =====================================
// Este archivo contiene funciones compartidas en todo el proyecto
// Incluir este archivo donde se necesiten estas funciones
// =====================================

/**
 * Valida que una contraseña cumpla con los requisitos de seguridad
 * 
 * Requisitos:
 * - Mínimo 8 caracteres
 * - Al menos una letra mayúscula (A-Z)
 * - Al menos una letra minúscula (a-z)
 * - Al menos un número (0-9)
 * - Al menos un carácter especial (!@#$%^&*())
 * 
 * @param string $password La contraseña a validar
 * @return array ['valid' => bool, 'message' => string]
 */
function validatePassword($password) {
    // Validar longitud mínima
    if (strlen($password) < 8) {
        return [
            'valid' => false, 
            'message' => 'La contraseña debe tener al menos 8 caracteres'
        ];
    }
    
    // Validar al menos una letra mayúscula
    if (!preg_match('/[A-Z]/', $password)) {
        return [
            'valid' => false, 
            'message' => 'La contraseña debe incluir al menos una letra mayúscula (A-Z)'
        ];
    }
    
    // Validar al menos una letra minúscula
    if (!preg_match('/[a-z]/', $password)) {
        return [
            'valid' => false, 
            'message' => 'La contraseña debe incluir al menos una letra minúscula (a-z)'
        ];
    }
    
    // Validar al menos un número
    if (!preg_match('/[0-9]/', $password)) {
        return [
            'valid' => false, 
            'message' => 'La contraseña debe incluir al menos un número (0-9)'
        ];
    }
    
    // Validar al menos un carácter especial
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        return [
            'valid' => false, 
            'message' => 'La contraseña debe incluir al menos un carácter especial (!@#$%^&*)'
        ];
    }
    
    // Si pasa todas las validaciones
    return [
        'valid' => true, 
        'message' => 'Contraseña válida'
    ];
}

/**
 * Sanitiza y valida un email
 * 
 * @param string $email El email a validar
 * @return array ['valid' => bool, 'email' => string|null, 'message' => string]
 */
function validateEmail($email) {
    // Limpiar el email
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    // Validar formato
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'valid' => false,
            'email' => null,
            'message' => 'El formato del email no es válido'
        ];
    }
    
    return [
        'valid' => true,
        'email' => $email,
        'message' => 'Email válido'
    ];
}

/**
 * Sanitiza una cadena de texto para prevenir XSS
 * 
 * @param string $string La cadena a sanitizar
 * @return string La cadena sanitizada
 */
function sanitizeString($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida que un campo no esté vacío
 * 
 * @param mixed $value El valor a validar
 * @param string $fieldName Nombre del campo (para el mensaje de error)
 * @return array ['valid' => bool, 'message' => string]
 */
function validateRequired($value, $fieldName = 'Este campo') {
    if (empty($value) || (is_string($value) && trim($value) === '')) {
        return [
            'valid' => false,
            'message' => $fieldName . ' es requerido'
        ];
    }
    
    return [
        'valid' => true,
        'message' => 'Campo válido'
    ];
}

/**
 * Genera un hash seguro de una contraseña
 * 
 * @param string $password La contraseña a hashear
 * @return string El hash de la contraseña
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifica si una contraseña coincide con un hash
 * 
 * @param string $password La contraseña en texto plano
 * @param string $hash El hash almacenado
 * @return bool True si coincide, false si no
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Registra un log de error
 * 
 * @param string $message El mensaje de error
 * @param string $context Contexto adicional (opcional)
 */
function logError($message, $context = '') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] ERROR: $message";
    
    if (!empty($context)) {
        $logMessage .= " | Context: $context";
    }
    
    error_log($logMessage);
}

/**
 * Registra un log de información
 * 
 * @param string $message El mensaje de información
 * @param string $context Contexto adicional (opcional)
 */
function logInfo($message, $context = '') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] INFO: $message";
    
    if (!empty($context)) {
        $logMessage .= " | Context: $context";
    }
    
    error_log($logMessage);
}

?>