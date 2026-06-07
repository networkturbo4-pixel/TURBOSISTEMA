<?php
require_once 'config/db.php';

// Si ya tiene sesión de cliente público, ir al portal
if (isset($_SESSION['public_cliente_id'])) {
    header('Location: portal.php');
    exit;
}

// Fetch global settings
$globalSettings = [];
try {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        $globalSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) { }

$primaryColor = $globalSettings['primary_color_light'] ?? '#064e3b';
$appName = $globalSettings['app_name'] ?? 'Turbo SaaS';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title>Portal del Cliente - <?php echo htmlspecialchars($appName); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script>
        // CSRF Fetch Interceptor
        const originalFetch = window.fetch;
        window.fetch = async function() {
            let [resource, config] = arguments;
            if (!config) config = {};
            if (config.method && config.method.toUpperCase() === 'POST') {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) {
                    if (config.body instanceof FormData) {
                        if(!config.body.has('csrf_token')) config.body.append('csrf_token', csrfToken);
                    } else if (typeof config.body === 'string') {
                        if (config.headers && config.headers['Content-Type'] === 'application/json') {
                            try {
                                let json = JSON.parse(config.body);
                                json.csrf_token = csrfToken;
                                config.body = JSON.stringify(json);
                            } catch(e) {}
                        } else if (config.headers && config.headers['Content-Type'] === 'application/x-www-form-urlencoded') {
                            config.body += config.body ? '&csrf_token=' + encodeURIComponent(csrfToken) : 'csrf_token=' + encodeURIComponent(csrfToken);
                        }
                    } else if (!config.body) {
                        config.body = new FormData();
                        config.body.append('csrf_token', csrfToken);
                    }
                }
            }
            return originalFetch(resource, config);
        };
    </script>
    <style>
        body {
            background-color: #0b0f19;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            color: #f8fafc;
            display: block !important; 
            height: 100dvh !important;
            overflow: hidden !important;
        }

        .split-layout {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        /* --- Panel Izquierdo --- */
        .split-left {
            flex: 1;
            background: linear-gradient(135deg, <?php echo htmlspecialchars($primaryColor); ?> 0%, #020617 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 80px;
            position: relative;
            overflow: hidden;
        }

        .split-left::before {
            content: '';
            position: absolute;
            top: -20%;
            left: -10%;
            width: 70%;
            height: 70%;
            background: radial-gradient(circle, <?php echo htmlspecialchars($primaryColor); ?>40 0%, transparent 70%);
            z-index: 1;
        }

        .left-content {
            position: relative;
            z-index: 2;
            max-width: 500px;
        }

        .left-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
            color: #f8fafc;
        }

        .left-content p {
            font-size: 1.1rem;
            color: #94a3b8;
            margin-bottom: 50px;
            max-width: 400px;
            line-height: 1.6;
        }

        /* --- Panel Derecho --- */
        .split-right {
            flex: 1;
            background-color: #0b0f19;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            overflow-y: auto;
        }

        .form-container {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.05);
            padding: 40px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .form-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .form-header h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #f8fafc;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #cbd5e1;
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            font-size: 1rem;
            background: rgba(255,255,255,0.05);
            color: #f8fafc;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
            text-align: center;
            letter-spacing: 2px;
            font-weight: bold;
        }
        .form-control:focus {
            outline: none;
            border-color: <?php echo htmlspecialchars($primaryColor); ?>;
            box-shadow: 0 0 0 3px <?php echo htmlspecialchars($primaryColor); ?>40;
            background: rgba(255,255,255,0.08);
        }

        .btn-primary {
            background: <?php echo htmlspecialchars($primaryColor); ?>;
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s, transform 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .error-message {
            display: none;
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(239, 68, 68, 0.2);
            text-align: center;
        }
        
        @media (max-width: 992px) {
            .split-left { display: none; }
            .split-right { padding: 20px; }
            .form-container { max-width: 100%; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="split-layout">
    <div class="split-left">
        <div class="left-content">
            <h1>Portal del Cliente</h1>
            <p>Accede a tu área personal para gestionar tus servicios, revisar tu plan actual, solicitar soporte y revisar tu historial de atención.</p>
        </div>
    </div>

    <div class="split-right">
        <div class="form-container">
            <div class="form-header">
                <h2>Ingresa a tu cuenta</h2>
                <p style="color: #94a3b8; font-size: 0.9rem;">Digita tu DNI o RUC para acceder</p>
            </div>

            <div id="errorBox" class="error-message"></div>

            <form id="publicLoginForm">
                <div class="form-group">
                    <label class="form-label" style="text-align: center;">Documento de Identidad</label>
                    <input type="text" id="dni_input" class="form-control" placeholder="Ej. 12345678" required autocomplete="off">
                </div>

                <button type="submit" class="btn-primary" id="btnSubmit">
                    Ingresar <i class="ph ph-arrow-right"></i>
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 25px; color: #64748b; font-size: 0.8rem;">
                En el futuro, enviaremos un código de seguridad a tu WhatsApp registrado.
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('publicLoginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const dni = document.getElementById('dni_input').value.trim();
        if(!dni) return;

        const errorBox = document.getElementById('errorBox');
        const btn = document.getElementById('btnSubmit');
        
        errorBox.style.display = 'none';
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Verificando...';

        const fd = new FormData();
        fd.append('action', 'public_login');
        fd.append('dni', dni);

        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if(res.success) {
                window.location.href = 'portal.php';
            } else {
                errorBox.innerText = res.message || 'Error al ingresar.';
                errorBox.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = 'Ingresar <i class="ph ph-arrow-right"></i>';
            }
        } catch(err) {
            errorBox.innerText = 'Error de conexión.';
            errorBox.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = 'Ingresar <i class="ph ph-arrow-right"></i>';
        }
    });
</script>

</body>
</html>
