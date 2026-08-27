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

if (isset($_SESSION['public_cliente_id'])) {
    header("Location: portal.php");
    exit;
}

// Generar CSRF token si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch global settings
$globalSettings = [];
try {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        $globalSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) { }

$appName = !empty($globalSettings['app_name']) ? $globalSettings['app_name'] : 'Turbo Perú';
$primaryColor = !empty($globalSettings['primary_color_light']) ? $globalSettings['primary_color_light'] : '#0e4194';
$primaryColorDark = !empty($globalSettings['primary_color_dark']) ? $globalSettings['primary_color_dark'] : '#f07d00';
$logoLight = !empty($globalSettings['logo_light']) ? $globalSettings['logo_light'] : '';
$logoDark = !empty($globalSettings['logo_dark']) ? $globalSettings['logo_dark'] : '';
$favicon = !empty($globalSettings['favicon']) ? $globalSettings['favicon'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - <?php echo htmlspecialchars($appName); ?></title>
    <?php if ($favicon && file_exists($favicon)): ?>
        <link rel="shortcut icon" href="<?php echo BASE_URL . '/' . htmlspecialchars($favicon); ?>" type="image/x-icon">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --brand-blue: #0e4194;
            --brand-blue-hover: #1d4ed8;
            --brand-blue-light: #38bdf8;
            --brand-orange: #f07d00;
            --brand-orange-hover: #ff8c00;
            --brand-orange-glow: rgba(240, 125, 0, 0.4);
            --brand-gradient: linear-gradient(135deg, #f07d00 0%, #0e4194 100%);
            --bg-dark: #070b14;
            --card-dark: rgba(13, 19, 34, 0.85);
            --border-glass: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --font-base: 13px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            user-select: none;
            -webkit-user-select: none;
        }

        body {
            background-color: var(--bg-dark);
            font-family: 'Outfit', 'Inter', sans-serif;
            font-size: var(--font-base);
            color: var(--text-main);
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* --- Ambient Background Glows (Naranja y Azul) --- */
        .ambient-bg {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(110px);
            opacity: 0.28;
            animation: float 20s infinite alternate ease-in-out;
        }
        .orb-orange {
            top: -10%;
            left: -5%;
            width: 580px;
            height: 580px;
            background: radial-gradient(circle, #f07d00 0%, transparent 70%);
        }
        .orb-blue {
            bottom: -15%;
            right: -10%;
            width: 620px;
            height: 620px;
            background: radial-gradient(circle, #0e4194 0%, transparent 70%);
            animation-duration: 25s;
        }
        .orb-accent {
            top: 40%;
            left: 45%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, #2563eb 0%, transparent 70%);
            opacity: 0.18;
            animation-duration: 18s;
        }
        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(45px, 35px) scale(1.12); }
        }

        /* --- Layout Container --- */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1160px;
            min-height: 620px;
            margin: 20px auto;
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            background: rgba(13, 19, 34, 0.85);
            border: 1px solid var(--border-glass);
            border-radius: 28px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.75), 0 0 45px rgba(240, 125, 0, 0.1), 0 0 50px rgba(14, 65, 148, 0.15);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            overflow: hidden;
        }

        /* --- Hero Showcase Panel (Left) --- */
        .showcase-panel {
            background: linear-gradient(145deg, rgba(240, 125, 0, 0.08) 0%, rgba(14, 65, 148, 0.18) 50%, rgba(15, 23, 42, 0.5) 100%);
            padding: 55px 50px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            border-right: 1px solid var(--border-glass);
        }

        .showcase-header {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .brand-logo-img {
            max-height: 52px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.4));
        }
        .brand-logo-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--brand-orange), var(--brand-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
            box-shadow: 0 8px 20px rgba(240, 125, 0, 0.35);
        }

        .showcase-body {
            margin: 35px 0;
        }
        .showcase-headline {
            font-size: 2.35rem;
            font-weight: 800;
            line-height: 1.18;
            letter-spacing: -0.8px;
            color: #f8fafc;
            margin-bottom: 16px;
        }
        .highlight-orange {
            background: linear-gradient(135deg, #ff9800 0%, #f07d00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .highlight-blue {
            background: linear-gradient(135deg, #38bdf8 0%, #2563eb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .showcase-desc {
            color: var(--text-muted);
            font-size: 0.98rem;
            line-height: 1.6;
            margin-bottom: 28px;
            max-width: 440px;
        }

        .features-list {
            display: flex;
            flex-direction: column;
            gap: 13px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.25s ease;
        }
        .feature-item:hover {
            background: rgba(255, 255, 255, 0.06);
            transform: translateX(4px);
            border-color: rgba(240, 125, 0, 0.25);
        }
        .feature-icon-orange {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(240, 125, 0, 0.15);
            color: #f07d00;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            border: 1px solid rgba(240, 125, 0, 0.25);
        }
        .feature-icon-blue {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(14, 65, 148, 0.2);
            color: #38bdf8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            border: 1px solid rgba(56, 189, 248, 0.25);
        }
        .feature-text h4 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #f1f5f9;
            margin-bottom: 2px;
        }
        .feature-text p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .showcase-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-muted);
            padding-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 12px;
            border-radius: 20px;
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.2);
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 10px #10b981;
            animation: pulse-dot 2s infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* --- Auth Panel (Right) --- */
        .auth-panel {
            padding: 45px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .auth-card {
            width: 100%;
            max-width: 380px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 22px;
        }
        .auth-icon-badge {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(240, 125, 0, 0.2) 0%, rgba(14, 65, 148, 0.3) 100%);
            border: 1.5px solid rgba(240, 125, 0, 0.35);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            color: #f07d00;
            margin-bottom: 12px;
            box-shadow: 0 8px 24px rgba(240, 125, 0, 0.15), 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        .auth-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.3px;
            margin-bottom: 6px;
        }
        .auth-subtitle {
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        /* --- 8-Digit PIN Display Slots (Orange & Blue accents) --- */
        .pin-slots-wrapper {
            margin: 18px 0 14px 0;
            position: relative;
        }
        .pin-slots {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 6px;
            padding: 10px 8px;
            background: rgba(0, 0, 0, 0.4);
            border: 1.5px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            cursor: pointer;
            transition: border-color 0.25s, box-shadow 0.25s;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }
        .pin-slots.focused {
            border-color: #f07d00;
            box-shadow: 0 0 0 3px rgba(240, 125, 0, 0.2), 0 0 20px rgba(14, 65, 148, 0.25);
        }
        .pin-slot {
            height: 48px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            font-weight: 700;
            color: #ff9800;
            font-family: 'Outfit', monospace;
            transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .pin-slot.filled {
            background: rgba(240, 125, 0, 0.12);
            border-color: rgba(240, 125, 0, 0.45);
            color: #ffffff;
            box-shadow: 0 2px 10px rgba(240, 125, 0, 0.2);
        }
        .pin-slot.active {
            border-color: #38bdf8;
            background: rgba(14, 65, 148, 0.15);
        }
        .pin-slot.active::after {
            content: '';
            position: absolute;
            bottom: 8px;
            width: 14px;
            height: 2px;
            background: #f07d00;
            border-radius: 2px;
            box-shadow: 0 0 6px #f07d00;
            animation: cursor-blink 1s infinite;
        }
        @keyframes cursor-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }

        .pin-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #f07d00;
            box-shadow: 0 0 10px #f07d00;
            animation: popIn 0.18s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .pin-char {
            animation: popIn 0.18s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            color: #ff9800;
        }
        @keyframes popIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        /* --- Toolbar under PIN: Visibility Toggle & Instruction --- */
        .pin-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 4px;
            margin-bottom: 15px;
        }
        .pin-hint {
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .pin-hint i {
            font-size: 0.95rem;
            color: #f07d00;
        }
        .btn-toggle-eye {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-toggle-eye:hover {
            color: #f8fafc;
            background: rgba(255, 255, 255, 0.06);
        }

        /* --- On-screen Numeric Keypad --- */
        .keypad-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }
        .key-btn {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 13px 0;
            font-size: 1.35rem;
            font-weight: 600;
            color: #f8fafc;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
            outline: none;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }
        .key-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(240, 125, 0, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
        }
        .key-btn:active, .key-btn.pressed {
            background: rgba(240, 125, 0, 0.2);
            border-color: #f07d00;
            color: #ff9800;
            transform: scale(0.95);
            box-shadow: 0 0 14px rgba(240, 125, 0, 0.35);
        }
        .key-btn.action-btn {
            font-size: 1.15rem;
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.02);
        }
        .key-btn.action-btn:hover {
            color: #f8fafc;
        }
        .key-btn.action-clear:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.3);
        }
        .key-btn.action-backspace:hover {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            border-color: rgba(245, 158, 11, 0.3);
        }

        /* --- Submit Button (Naranja y Azul) --- */
        .btn-auth-submit {
            width: 100%;
            background: linear-gradient(135deg, #0e4194 0%, #1d4ed8 45%, #f07d00 100%);
            background-size: 200% auto;
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 14px;
            padding: 14px 20px;
            font-size: 0.98rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 25px -5px rgba(14, 65, 148, 0.5), 0 4px 15px rgba(240, 125, 0, 0.3);
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-auth-submit:hover:not(:disabled) {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -5px rgba(240, 125, 0, 0.5), 0 0 25px rgba(14, 65, 148, 0.4);
            filter: brightness(1.08);
        }
        .btn-auth-submit:active:not(:disabled) {
            transform: translateY(0);
        }
        .btn-auth-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* --- Error & Notification Box --- */
        .error-alert {
            display: none;
            margin-top: 14px;
            padding: 10px 14px;
            border-radius: 12px;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            font-size: 0.85rem;
            align-items: center;
            gap: 8px;
            animation: fadeIn 0.2s ease;
        }
        .error-alert.show {
            display: flex;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Shake animation for invalid attempt */
        .shake {
            animation: shakeAnim 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }
        @keyframes shakeAnim {
            10%, 90% { transform: translate3d(-2px, 0, 0); }
            20%, 80% { transform: translate3d(4px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-6px, 0, 0); }
            40%, 60% { transform: translate3d(6px, 0, 0); }
        }

        /* --- Footer Links --- */
        .auth-footer {
            margin-top: 18px;
            text-align: center;
        }
        .link-help {
            color: var(--text-muted);
            font-size: 0.82rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: color 0.2s;
        }
        .link-help:hover {
            color: #f07d00;
            text-decoration: underline;
        }

        /* --- Modal for Help / Recovery --- */
        .help-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .help-modal-overlay.active {
            display: flex;
        }
        .help-modal-card {
            background: #0f172a;
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            max-width: 440px;
            width: 100%;
            padding: 28px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
            animation: popIn 0.25s ease;
        }
        .help-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .help-modal-header h3 {
            font-size: 1.15rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .help-modal-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.3rem;
            cursor: pointer;
        }
        .help-modal-close:hover { color: #fff; }
        .help-modal-body {
            color: #cbd5e1;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .help-modal-btn {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0e4194, #f07d00);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .help-modal-btn:hover {
            opacity: 0.9;
        }

        /* --- Responsive Styles --- */
        @media (max-width: 980px) {
            .login-wrapper {
                grid-template-columns: 1fr;
                max-width: 480px;
                min-height: auto;
                border-radius: 22px;
            }
            .showcase-panel {
                display: none;
            }
            .auth-panel {
                padding: 40px 24px;
            }
            .pin-slot {
                height: 44px;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 420px) {
            .pin-slots {
                gap: 4px;
                padding: 8px 4px;
            }
            .pin-slot {
                height: 40px;
                font-size: 1.05rem;
                border-radius: 8px;
            }
            .key-btn {
                padding: 10px 0;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>

<div class="ambient-bg">
    <div class="orb orb-orange"></div>
    <div class="orb orb-blue"></div>
    <div class="orb orb-accent"></div>
</div>

<div class="login-wrapper">
    <!-- Panel Izquierdo: Showcase de Marca y Características -->
    <div class="showcase-panel">
        <div class="showcase-header">
            <?php if (!empty($logoLight) && file_exists($logoLight)): ?>
                <img src="<?php echo BASE_URL . '/' . htmlspecialchars($logoLight); ?>" alt="<?php echo htmlspecialchars($appName); ?>" class="brand-logo-img">
            <?php elseif (!empty($logoDark) && file_exists($logoDark)): ?>
                <img src="<?php echo BASE_URL . '/' . htmlspecialchars($logoDark); ?>" alt="<?php echo htmlspecialchars($appName); ?>" class="brand-logo-img">
            <?php else: ?>
                <div class="brand-logo-icon">
                    <i class="ph-bold ph-lightning"></i>
                </div>
            <?php endif; ?>
        </div>

        <div class="showcase-body">
            <h1 class="showcase-headline">
                Tu plataforma de <span class="highlight-orange">conectividad</span> & <span class="highlight-blue">gestión</span> integral.
            </h1>
            <p class="showcase-desc">
                Acceso unificado para administradores, técnicos de campo y clientes con la máxima seguridad y velocidad operativa.
            </p>

            <div class="features-list">
                <div class="feature-item">
                    <div class="feature-icon-orange">
                        <i class="ph-bold ph-key"></i>
                    </div>
                    <div class="feature-text">
                        <h4>PIN Único de 8 Dígitos</h4>
                        <p>Autenticación segura y rápida desde cualquier teclado o pantalla táctil.</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon-blue">
                        <i class="ph-bold ph-shield-check"></i>
                    </div>
                    <div class="feature-text">
                        <h4>Protección Anti Fuerza Bruta</h4>
                        <p>Detección inteligente de intentos fallidos y encriptación de credenciales.</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon-orange">
                        <i class="ph-bold ph-device-mobile"></i>
                    </div>
                    <div class="feature-text">
                        <h4>Experiencia Multiplataforma</h4>
                        <p>Optimizado para terminales de campo, computadoras y smartphones.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="showcase-footer">
            <div class="status-pill">
                <span class="status-dot"></span>
                <span>Sistema Operativo y Seguro</span>
            </div>
            <span>© <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName); ?></span>
        </div>
    </div>

    <!-- Panel Derecho: Formulario e Interfaz de PIN -->
    <div class="auth-panel">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon-badge">
                    <i class="ph-bold ph-lock-key"></i>
                </div>
                <h2 class="auth-title">Iniciar Sesión</h2>
                <p class="auth-subtitle">Ingresa tu PIN de 8 dígitos con el teclado o en pantalla</p>
            </div>

            <!-- PIN Display 8 Slots -->
            <div class="pin-slots-wrapper" id="pinSlotsWrapper">
                <div class="pin-slots focused" id="pinSlotsContainer">
                    <div class="pin-slot active" data-index="0"></div>
                    <div class="pin-slot" data-index="1"></div>
                    <div class="pin-slot" data-index="2"></div>
                    <div class="pin-slot" data-index="3"></div>
                    <div class="pin-slot" data-index="4"></div>
                    <div class="pin-slot" data-index="5"></div>
                    <div class="pin-slot" data-index="6"></div>
                    <div class="pin-slot" data-index="7"></div>
                </div>
            </div>

            <!-- Toolbar: Helper & Show/Hide Toggle -->
            <div class="pin-toolbar">
                <span class="pin-hint">
                    <i class="ph-fill ph-shield-check"></i> Acceso seguro por PIN
                </span>
                <button type="button" class="btn-toggle-eye" id="btnToggleVisibility" onclick="togglePinVisibility()">
                    <i class="ph ph-eye" id="eyeIcon"></i> <span id="eyeText">Mostrar</span>
                </button>
            </div>

            <!-- On-screen Tactile Keypad -->
            <div class="keypad-grid">
                <button type="button" class="key-btn" data-key="1">1</button>
                <button type="button" class="key-btn" data-key="2">2</button>
                <button type="button" class="key-btn" data-key="3">3</button>
                <button type="button" class="key-btn" data-key="4">4</button>
                <button type="button" class="key-btn" data-key="5">5</button>
                <button type="button" class="key-btn" data-key="6">6</button>
                <button type="button" class="key-btn" data-key="7">7</button>
                <button type="button" class="key-btn" data-key="8">8</button>
                <button type="button" class="key-btn" data-key="9">9</button>
                <button type="button" class="key-btn action-btn action-clear" id="btnKeyClear" title="Limpiar PIN (Esc / C)">
                    <i class="ph-bold ph-x"></i>
                </button>
                <button type="button" class="key-btn" data-key="0">0</button>
                <button type="button" class="key-btn action-btn action-backspace" id="btnKeyBackspace" title="Borrar último (Backspace)">
                    <i class="ph-bold ph-backspace"></i>
                </button>
            </div>

            <!-- Action Button -->
            <button type="button" class="btn-auth-submit" id="btnSubmitLogin" onclick="submitAuth()">
                <i class="ph-bold ph-sign-in"></i>
                <span id="btnSubmitText">Ingresar al Sistema</span>
            </button>

            <!-- Error message container -->
            <div class="error-alert" id="authErrorAlert">
                <i class="ph-bold ph-warning-circle" style="font-size: 1.2rem; flex-shrink: 0;"></i>
                <span id="authErrorText">PIN incorrecto o no registrado.</span>
            </div>

            <!-- Footer info -->
            <div class="auth-footer">
                <a href="javascript:void(0)" class="link-help" onclick="openHelpModal()">
                    <i class="ph ph-question"></i> ¿Olvidaste o no tienes tu PIN de 8 dígitos?
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Ayuda / Recuperación -->
<div class="help-modal-overlay" id="helpModal" onclick="closeHelpModal(event)">
    <div class="help-modal-card" onclick="event.stopPropagation()">
        <div class="help-modal-header">
            <h3><i class="ph-bold ph-info" style="color: #f07d00;"></i> Acceso con PIN</h3>
            <button type="button" class="help-modal-close" onclick="closeHelpModal()">&times;</button>
        </div>
        <div class="help-modal-body">
            <p><strong>¿Cómo funciona el acceso?</strong></p>
            <p style="margin-top: 6px;">Cada usuario (Administrador, Técnico o Cliente) dispone de un código numérico exclusivo de <strong>8 dígitos</strong> para ingresar rápidamente al sistema.</p>
            <p style="margin-top: 12px;"><strong>Si eres técnico o administrador:</strong> Contacta al administrador principal de <?php echo htmlspecialchars($appName); ?> para consultar o regenerar tu PIN en el panel de Ajustes.</p>
            <p style="margin-top: 12px;"><strong>Si eres cliente:</strong> Tu PIN de 8 dígitos fue asignado al momento del registro de tu contrato/servicio o puedes solicitarlo a la línea de soporte.</p>
        </div>
        <button type="button" class="help-modal-btn" onclick="closeHelpModal()">Entendido</button>
    </div>
</div>

<script>
    let currentPin = '';
    let isVisible = false;
    let isSubmitting = false;

    const slotsContainer = document.getElementById('pinSlotsContainer');
    const slotElements = document.querySelectorAll('.pin-slot');
    const errorAlert = document.getElementById('authErrorAlert');
    const errorText = document.getElementById('authErrorText');
    const btnSubmit = document.getElementById('btnSubmitLogin');
    const btnSubmitText = document.getElementById('btnSubmitText');
    const eyeIcon = document.getElementById('eyeIcon');
    const eyeText = document.getElementById('eyeText');

    // --- RENDER PIN SLOTS ---
    function renderSlots() {
        slotElements.forEach((slot, index) => {
            slot.className = 'pin-slot';
            slot.innerHTML = '';

            if (index < currentPin.length) {
                slot.classList.add('filled');
                if (isVisible) {
                    slot.innerHTML = `<span class="pin-char">${currentPin[index]}</span>`;
                } else {
                    slot.innerHTML = '<span class="pin-dot"></span>';
                }
            } else if (index === currentPin.length) {
                slot.classList.add('active');
            }
        });

        // Hide error when user modifies pin
        if (errorAlert.classList.contains('show')) {
            errorAlert.classList.remove('show');
        }
    }

    // --- ADD DIGIT ---
    function addDigit(digit) {
        if (isSubmitting) return;
        if (currentPin.length < 8) {
            currentPin += String(digit);
            renderSlots();

            // Auto-submit when exactly 8 digits are entered
            if (currentPin.length === 8) {
                setTimeout(() => {
                    submitAuth();
                }, 120);
            }
        }
    }

    // --- DELETE DIGIT ---
    function deleteDigit() {
        if (isSubmitting) return;
        if (currentPin.length > 0) {
            currentPin = currentPin.slice(0, -1);
            renderSlots();
        }
    }

    // --- CLEAR PIN ---
    function clearPin() {
        if (isSubmitting) return;
        currentPin = '';
        renderSlots();
    }

    // --- TOGGLE SHOW / HIDE ---
    function togglePinVisibility() {
        isVisible = !isVisible;
        if (isVisible) {
            eyeIcon.className = 'ph ph-eye-slash';
            eyeText.innerText = 'Ocultar';
        } else {
            eyeIcon.className = 'ph ph-eye';
            eyeText.innerText = 'Mostrar';
        }
        renderSlots();
    }

    // --- PHYSICAL KEYBOARD & NUMPAD LISTENERS (DESKTOP) ---
    window.addEventListener('keydown', (e) => {
        if (isSubmitting) return;

        // Ignore if typing inside any form input or textarea
        if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) {
            return;
        }

        // Number keys (0-9 from top row and Numpad)
        if ((e.key >= '0' && e.key <= '9') || (e.code && e.code.startsWith('Numpad') && e.key >= '0' && e.key <= '9')) {
            e.preventDefault();
            addDigit(e.key);
            highlightKey(e.key);
        } else if (e.key === 'Backspace') {
            e.preventDefault();
            deleteDigit();
            highlightActionKey('btnKeyBackspace');
        } else if (e.key === 'Delete' || e.key === 'Escape' || e.key === 'c' || e.key === 'C') {
            e.preventDefault();
            clearPin();
            highlightActionKey('btnKeyClear');
        } else if (e.key === 'Enter') {
            e.preventDefault();
            submitAuth();
        }
    });

    // Capture paste events (e.g. copying 8 digit pin)
    window.addEventListener('paste', (e) => {
        if (isSubmitting) return;
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const digits = pastedText.replace(/\D/g, '').slice(0, 8);
        if (digits.length > 0) {
            currentPin = digits;
            renderSlots();
            if (currentPin.length === 8) {
                setTimeout(() => submitAuth(), 120);
            }
        }
    });

    // --- KEYPAD BUTTON CLICKS & TOUCH ---
    document.querySelectorAll('.key-btn[data-key]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const digit = btn.getAttribute('data-key');
            addDigit(digit);
        });
    });

    document.getElementById('btnKeyBackspace').addEventListener('click', (e) => {
        e.preventDefault();
        deleteDigit();
    });

    document.getElementById('btnKeyClear').addEventListener('click', (e) => {
        e.preventDefault();
        clearPin();
    });

    function highlightKey(digit) {
        const btn = document.querySelector(`.key-btn[data-key="${digit}"]`);
        if (btn) {
            btn.classList.add('pressed');
            setTimeout(() => btn.classList.remove('pressed'), 140);
        }
    }

    function highlightActionKey(btnId) {
        const btn = document.getElementById(btnId);
        if (btn) {
            btn.classList.add('pressed');
            setTimeout(() => btn.classList.remove('pressed'), 140);
        }
    }

    // --- AUTH SUBMIT VIA AJAX ---
    async function submitAuth() {
        if (isSubmitting) return;

        if (currentPin.length === 0) {
            showError('Por favor, ingresa tu PIN de 8 dígitos.');
            triggerShake();
            return;
        }

        if (currentPin.length < 8) {
            showError(`El PIN debe contener 8 dígitos (llevas ${currentPin.length}).`);
            triggerShake();
            return;
        }

        isSubmitting = true;
        btnSubmit.disabled = true;
        btnSubmitText.innerHTML = '<i class="ph-bold ph-spinner ph-spin" style="animation: spin 0.8s linear infinite;"></i> Verificando PIN...';

        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('pin', currentPin);
        formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

        try {
            const response = await fetch('<?php echo BASE_URL; ?>/ajax/auth.php', {
                method: 'POST',
                body: formData
            });

            const res = await response.json();

            if (res.success) {
                btnSubmitText.innerHTML = '<i class="ph-bold ph-check-circle"></i> ¡Acceso concedido!';
                btnSubmit.style.background = '#10b981';
                setTimeout(() => {
                    const destination = res.redirect ? ('<?php echo BASE_URL; ?>/' + res.redirect) : '<?php echo BASE_URL; ?>/index.php';
                    window.location.href = destination;
                }, 350);
            } else {
                showError(res.message || 'PIN incorrecto.');
                triggerShake();
                clearPin();
                isSubmitting = false;
                btnSubmit.disabled = false;
                btnSubmitText.innerHTML = '<i class="ph-bold ph-sign-in"></i> Ingresar al Sistema';
            }
        } catch (error) {
            console.error(error);
            showError('Error de comunicación con el servidor.');
            triggerShake();
            isSubmitting = false;
            btnSubmit.disabled = false;
            btnSubmitText.innerHTML = '<i class="ph-bold ph-sign-in"></i> Ingresar al Sistema';
        }
    }

    function showError(msg) {
        errorText.innerText = msg;
        errorAlert.classList.add('show');
    }

    function triggerShake() {
        const wrapper = document.getElementById('pinSlotsWrapper');
        wrapper.classList.remove('shake');
        void wrapper.offsetWidth; // trigger reflow
        wrapper.classList.add('shake');
        setTimeout(() => wrapper.classList.remove('shake'), 450);
    }

    // --- HELP MODAL ---
    function openHelpModal() {
        document.getElementById('helpModal').classList.add('active');
    }

    function closeHelpModal(e) {
        if (!e || e.target === document.getElementById('helpModal') || e.target.classList.contains('help-modal-close') || e.target.classList.contains('help-modal-btn')) {
            document.getElementById('helpModal').classList.remove('active');
        }
    }
</script>

<style>
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>
</body>
</html>
