<?php
/**
 * Funciones auxiliares del sistema
 */

/**
 * Genera un slug URL-friendly
 */
function slugify($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Sanitiza salida HTML
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Parsea JSON de forma segura
 */
function sane_json($json_string) {
    if (empty($json_string)) return [];
    $decoded = json_decode($json_string, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Convierte array a JSON para BD
 */
function to_json($array) {
    return json_encode($array ?: [], JSON_UNESCAPED_UNICODE);
}

/**
 * Formatea precio
 */
function format_price($price, $currency = 'USD') {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'PEN' => 'S/.'
    ];
    
    $symbol = $symbols[$currency] ?? $currency;
    return $symbol . number_format($price, 0);
}

/**
 * Calcula disponibilidad de asientos
 */
function seats_available($total, $taken) {
    return max(0, $total - $taken);
}

/**
 * Genera token CSRF
 */
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 */
function csrf_verify($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formatea fecha
 */
function format_date($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

/**
 * Calcula días entre fechas
 */
function days_diff($start, $end) {
    $start_date = new DateTime($start);
    $end_date = new DateTime($end);
    return $start_date->diff($end_date)->days + 1;
}

/**
 * Traduce dificultad
 */
function translate_difficulty($difficulty) {
    $translations = [
        'easy' => 'Fácil',
        'medium' => 'Moderado',
        'hard' => 'Difícil'
    ];
    return $translations[$difficulty] ?? $difficulty;
}

/**
 * Traduce estado de reserva
 */
function translate_status($status) {
    $translations = [
        'pending' => 'Pendiente',
        'confirmed' => 'Confirmada',
        'cancelled' => 'Cancelada'
    ];
    return $translations[$status] ?? $status;
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Validar email
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Truncar texto
 */
function truncate($text, $length = 150) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}
?>
