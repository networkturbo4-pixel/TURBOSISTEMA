<?php
require_once 'config/db.php';

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['public_cliente_id'])) {
        header("Location: portal.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// Fetch global settings for colors
$globalSettings = [];
try {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        $globalSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) { }

$primaryColor = $globalSettings['primary_color_light'] ?? '#064e3b';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Turbo SaaS</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0b0f19; /* Dark background */
            font-family: 'Inter', sans-serif;
            color: #f8fafc;
        }
        
        .split-layout {
            display: flex;
            height: 100vh;
            height: 100dvh;
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

        .steps-container {
            display: flex;
            gap: 15px;
        }

        .step-card {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            backdrop-filter: blur(10px);
            transition: transform 0.3s, background 0.3s;
        }

        .step-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }

        .step-card.active {
            background: #ffffff;
            color: #0f172a;
            border-color: #ffffff;
        }

        .step-number {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 12px;
            background: rgba(255, 255, 255, 0.2);
            color: #f8fafc;
        }

        .step-card.active .step-number {
            background: #0f172a;
            color: #ffffff;
        }

        .step-title {
            font-size: 0.95rem;
            font-weight: 600;
            line-height: 1.4;
            color: inherit;
        }

        .step-card:not(.active) .step-title {
            color: #cbd5e1;
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
        }

        .form-container {
            width: 100%;
            max-width: 380px;
        }

        .form-header {
            margin-bottom: 40px;
            text-align: center;
        }

        .form-header h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #f8fafc;
        }

        .form-header p {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        .auth-tabs {
            display: flex;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 5px;
            margin-bottom: 30px;
        }

        .auth-tab {
            flex: 1;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            color: #94a3b8;
            transition: all 0.3s;
        }

        .auth-tab.active {
            background: rgba(255,255,255,0.1);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #cbd5e1;
        }

        .form-control-dark {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #f8fafc;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
        }

        .form-control-dark:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
            background: rgba(255,255,255,0.08);
        }

        .btn-submit {
            width: 100%;
            background: #ffffff;
            color: #0f172a;
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
        }

        .btn-submit:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }

        /* --- Teclado PIN en pantalla --- */
        .keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .keypad-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #f8fafc;
            border-radius: 12px;
            padding: 20px 0;
            font-size: 1.5rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, transform 0.1s;
        }

        .keypad-btn:hover {
            background: rgba(255,255,255,0.1);
        }

        .keypad-btn:active {
            background: rgba(255,255,255,0.15);
            transform: scale(0.95);
        }

        .keypad-btn.action {
            font-size: 1.3rem;
            color: #94a3b8;
            background: transparent;
            border-color: transparent;
        }

        .keypad-btn.action:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .pin-display {
            text-align: center;
            letter-spacing: 12px;
            font-size: 2.5rem;
            font-weight: 700;
            color: #10b981;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .error-msg {
            display: none;
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.9rem;
            text-align: center;
            font-weight: 500;
        }

        @media (max-width: 992px) {
            .split-left { display: none; }
            .form-container { max-width: 100%; padding: 0 20px; }
            .split-right { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="split-layout">
    <div class="split-left">
        <div class="left-content">
            <h1>Bienvenido a<br>TurboSaaS</h1>
            <p>Tu plataforma integral para la gestión de actas, servicios e inventario. Completa el acceso para entrar a tu espacio de trabajo.</p>
            
            <div class="steps-container">
                <div class="step-card active">
                    <div class="step-number">1</div>
                    <div class="step-title">Ingresa tu<br>cuenta</div>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-title">Configura tu<br>espacio</div>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-title">Gestiona<br>servicios</div>
                </div>
            </div>
        </div>
    </div>

    <div class="split-right">
        <div class="form-container">
            <div class="form-header">
                <h2>Iniciar Sesión</h2>
                <p>Ingresa tus datos personales para acceder a tu cuenta.</p>
            </div>

            <div class="auth-tabs">
                <div class="auth-tab active" id="tabEmail" onclick="switchTab('email')">Correo</div>
                <div class="auth-tab" id="tabPin" onclick="switchTab('pin')">PIN</div>
            </div>

            <form id="loginForm" novalidate>
                <input type="hidden" name="login_type" id="login_type" value="email">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div id="viewEmail">
                    <div class="form-group">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control-dark" placeholder="ej. john@turbosaas.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control-dark" placeholder="Ingresa tu contraseña">
                    </div>
                </div>

                <div id="viewPin" style="display: none;">
                    <input type="hidden" name="pin" id="inputPinValue">
                    <div class="pin-display" id="pinDisplay"></div>
                    
                    <div class="keypad">
                        <button type="button" class="keypad-btn" onclick="addPin('1')">1</button>
                        <button type="button" class="keypad-btn" onclick="addPin('2')">2</button>
                        <button type="button" class="keypad-btn" onclick="addPin('3')">3</button>
                        <button type="button" class="keypad-btn" onclick="addPin('4')">4</button>
                        <button type="button" class="keypad-btn" onclick="addPin('5')">5</button>
                        <button type="button" class="keypad-btn" onclick="addPin('6')">6</button>
                        <button type="button" class="keypad-btn" onclick="addPin('7')">7</button>
                        <button type="button" class="keypad-btn" onclick="addPin('8')">8</button>
                        <button type="button" class="keypad-btn" onclick="addPin('9')">9</button>
                        <button type="button" class="keypad-btn action" onclick="clearPin()"><i class="ph ph-x"></i></button>
                        <button type="button" class="keypad-btn" onclick="addPin('0')">0</button>
                        <button type="button" class="keypad-btn action" onclick="deletePin()"><i class="ph ph-backspace"></i></button>
                    </div>
                </div>

                <div class="error-msg" id="loginError"></div>

                <button type="submit" class="btn-submit" id="btnSubmit">Ingresar al Sistema</button>
            </form>
        </div>
    </div>
</div>

<script>
    let currentPin = '';

    function switchTab(type) {
        document.getElementById('login_type').value = type;
        document.getElementById('loginError').style.display = 'none';

        if (type === 'email') {
            document.getElementById('tabEmail').classList.add('active');
            document.getElementById('tabPin').classList.remove('active');
            document.getElementById('viewEmail').style.display = 'block';
            document.getElementById('viewPin').style.display = 'none';
        } else {
            document.getElementById('tabPin').classList.add('active');
            document.getElementById('tabEmail').classList.remove('active');
            document.getElementById('viewPin').style.display = 'block';
            document.getElementById('viewEmail').style.display = 'none';
        }
    }

    function updatePinDisplay() {
        const display = document.getElementById('pinDisplay');
        display.innerText = '•'.repeat(currentPin.length);
        document.getElementById('inputPinValue').value = currentPin;
    }

    function addPin(num) {
        if (currentPin.length < 6) { 
            currentPin += num;
            updatePinDisplay();
        }
    }

    function deletePin() {
        if (currentPin.length > 0) {
            currentPin = currentPin.slice(0, -1);
            updatePinDisplay();
        }
    }

    function clearPin() {
        currentPin = '';
        updatePinDisplay();
    }

    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const loginType = document.getElementById('login_type').value;
        const errorDiv = document.getElementById('loginError');
        const btnSubmit = document.getElementById('btnSubmit');
        
        if (loginType === 'pin' && currentPin.length === 0) {
            errorDiv.innerText = 'Ingresa tu PIN usando el teclado numérico';
            errorDiv.style.display = 'block';
            return;
        }

        errorDiv.style.display = 'none';
        btnSubmit.disabled = true;
        btnSubmit.innerText = 'Verificando...';

        const formData = new FormData(e.target);
        formData.append('action', 'login');
        
        try {
            const response = await fetch('<?php echo BASE_URL; ?>/ajax/auth.php', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();
            
            if (res.success) {
                window.location.href = res.redirect ? ('<?php echo BASE_URL; ?>/' + res.redirect) : '<?php echo BASE_URL; ?>/index.php';
            } else {
                errorDiv.innerText = res.message || 'Error al iniciar sesión.';
                errorDiv.style.display = 'block';
                btnSubmit.disabled = false;
                btnSubmit.innerText = 'Ingresar al Sistema';
                
                if(loginType === 'pin') clearPin();
            }
        } catch (error) {
            console.error(error);
            errorDiv.innerText = 'Error de conexión con el servidor.';
            errorDiv.style.display = 'block';
            btnSubmit.disabled = false;
            btnSubmit.innerText = 'Ingresar al Sistema';
        }
    });
</script>
</body>
</html>
