<?php
/**
 * Asistente para Generar el Refresh Token de OAuth 2.0 para Google Drive
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/google_drive.php';

header('Content-Type: text/html; charset=utf-8');

$redirectUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . strtok($_SERVER["REQUEST_URI"], '?');

$clientId = GDRIVE_CLIENT_ID;
$clientSecret = GDRIVE_CLIENT_SECRET;

function save_config_val($key, $value) {
    $envFile = __DIR__ . '/config/env.php';
    if (file_exists($envFile)) {
        $content = file_get_contents($envFile);
        $pattern = "/define\('{$key}',\s*'.*?'\);/";
        $replacement = "define('{$key}', '{$value}');";
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            $content = rtrim($content);
            if (substr($content, -2) === '?>') {
                $content = substr($content, 0, -2) . "\ndefine('{$key}', '{$value}');\n?>";
            } else {
                $content .= "\ndefine('{$key}', '{$value}');\n";
            }
        }
        file_put_contents($envFile, $content);
        return 'config/env.php';
    } else {
        $configFile = __DIR__ . '/config/google_drive.php';
        $content = file_get_contents($configFile);
        $pattern = "/define\('{$key}',\s*'.*?'\);/";
        $replacement = "define('{$key}', '{$value}');";
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
            file_put_contents($configFile, $content);
        }
        return 'config/google_drive.php';
    }
}

// Si se recibe petición de guardado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_keys'])) {
    $cId = trim($_POST['client_id']);
    $cSec = trim($_POST['client_secret']);
    $rTok = trim($_POST['refresh_token'] ?? '');

    $savedIn = save_config_val('GDRIVE_CLIENT_ID', $cId);
    save_config_val('GDRIVE_CLIENT_SECRET', $cSec);
    if (!empty($rTok)) {
        save_config_val('GDRIVE_REFRESH_TOKEN', $rTok);
    }

    echo "<div style='padding:15px; background:#dcfce7; color:#15803d; border-radius:8px; margin-bottom:20px; font-family:sans-serif;'>✓ Credenciales guardadas exitosamente en {$savedIn}</div>";
    
    // Recargar valores
    $clientId = $cId;
    $clientSecret = $cSec;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador OAuth 2.0 - Google Drive</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; max-width: 700px; margin: 40px auto; padding: 20px; }
        .card { background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        h1 { font-size: 24px; color: #0f172a; margin-top: 0; }
        label { display: block; margin-top: 15px; font-weight: 600; font-size: 14px; }
        input[type="text"] { width: 100%; padding: 10px 14px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-family: monospace; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 20px; border: none; cursor: pointer; }
        .btn-success { background: #16a34a; }
        .code-box { background: #0f172a; color: #38bdf8; padding: 15px; border-radius: 6px; font-family: monospace; word-break: break-all; margin-top: 10px; }
        .step { background: #f1f5f9; padding: 15px; border-left: 4px solid #2563eb; margin: 15px 0; border-radius: 0 6px 6px 0; }
    </style>
</head>
<body>
<div class="card">
    <h1>🔑 Asistente de Configuración OAuth 2.0 - Google Drive</h1>
    
    <div class="step">
        <strong>Paso 1: Configurar Client ID y Client Secret</strong>
        <p>Crea una credencial del tipo "ID de cliente OAuth 2.0" en tu Google Cloud Console (Tipo: Aplicación Web) y añade esta URL de redirección exacta en la consola:</p>
        <code><?php echo htmlspecialchars($redirectUri); ?></code>
    </div>

    <form method="POST">
        <label>Client ID:</label>
        <input type="text" name="client_id" value="<?php echo htmlspecialchars($clientId); ?>" required placeholder="ej: 10559779...apps.googleusercontent.com">

        <label>Client Secret:</label>
        <input type="text" name="client_secret" value="<?php echo htmlspecialchars($clientSecret); ?>" required placeholder="ej: GOCSPX-...">

        <button type="submit" name="save_keys" class="btn">Guardar Claves</button>
    </form>

    <hr style="margin: 30px 0; border: none; border-top: 1px solid #e2e8f0;">

    <?php if (!empty($clientId) && !empty($clientSecret)): ?>
        <?php
        $client = new Google\Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->addScope(Google\Service\Drive::DRIVE);

        if (isset($_GET['code'])) {
            try {
                $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
                if (isset($token['refresh_token'])) {
                    $refreshToken = $token['refresh_token'];
                    
                    // Auto-guardar en env.php o google_drive.php
                    $savedIn = save_config_val('GDRIVE_REFRESH_TOKEN', $refreshToken);

                    echo "<h3 style='color:#16a34a;'>¡Refresh Token Generado y Guardado Automáticamente en {$savedIn}! 🎉</h3>";
                    echo "<p>Tu token de actualización es:</p>";
                    echo "<div class='code-box'>" . htmlspecialchars($refreshToken) . "</div>";
                    echo "<p style='margin-top:20px;'><a href='test_gdrive.php' class='btn btn-success'>🚀 Probar Subida a Google Drive Ahora</a></p>";
                } else {
                    echo "<h3 style='color:#dc2626;'>Atención: No se devolvió refresh_token</h3>";
                    echo "<p>Esto sucede si ya habías autorizado previamente. <a href='" . htmlspecialchars($client->createAuthUrl()) . "' class='btn'>Volver a Autorizar (Forzando Consentimiento)</a></p>";
                }
            } catch (Exception $e) {
                echo "<h3 style='color:#dc2626;'>Error al canjear el código:</h3>";
                echo "<pre style='color:red; background:#fee2e2; padding:10px;'>" . htmlspecialchars($e->getMessage()) . "</pre>";
            }
        } else {
            $authUrl = $client->createAuthUrl();
            echo "<strong>Paso 2: Autorizar con tu Cuenta de Google</strong>";
            echo "<p>Haz clic en el siguiente botón para iniciar sesión con tu cuenta de Google Drive y conceder permisos de almacenamiento:</p>";
            echo "<a href='" . htmlspecialchars($authUrl) . "' class='btn btn-success'>🔐 Conectar con Google Drive</a>";
        }
        ?>
    <?php else: ?>
        <p style="color: #64748b;">Ingresa tu <strong>Client ID</strong> y <strong>Client Secret</strong> arriba para habilitar el botón de vinculación.</p>
    <?php endif; ?>
</div>
</body>
</html>
