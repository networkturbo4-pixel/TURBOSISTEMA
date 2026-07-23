<?php
// Cargar configuraciones globales
$globalSettings = [];
try {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        $globalSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) { }

// Block clients from accessing admin pages
if (isset($_SESSION['user_role']) && strtolower(trim($_SESSION['user_role'])) === 'cliente') {
    header("Location: " . BASE_URL . "/portal.php");
    exit;
}

// Generar CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$appName = $globalSettings['app_name'] ?? 'Turbo SaaS';
$favicon = $globalSettings['favicon'] ?? '';
$bgColor = $globalSettings['bg_color'] ?? '#f4f6f9';
$textColor = $globalSettings['text_color'] ?? '#333333';
$typography = $globalSettings['typography'] ?? 'Inter';
$fontLink = "https://fonts.googleapis.com/css2?family=" . urlencode($typography) . ":wght@400;500;600;700&display=swap";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo BASE_URL; ?>">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title><?php echo htmlspecialchars($appName); ?></title>
    <?php if ($favicon): ?>
    <link rel="icon" href="<?php echo BASE_URL . '/' . $favicon; ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/gdrive_manager.css?v=<?php echo time(); ?>">
    <link href="<?php echo $fontLink; ?>" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --bg-color: <?php echo htmlspecialchars($bgColor); ?>;
            --text-color: <?php echo htmlspecialchars($textColor); ?>;
            --primary-color-light: <?php echo htmlspecialchars($globalSettings['primary_color_light'] ?? '#111827'); ?>;
            --primary-color-dark: <?php echo htmlspecialchars($globalSettings['primary_color_dark'] ?? '#4361ee'); ?>;
        }
        body:not(.dark-theme) {
            --primary-color: var(--primary-color-light) !important;
        }
        body.dark-theme {
            --primary-color: var(--primary-color-dark) !important;
        }
        * {
            font-family: '<?php echo htmlspecialchars($typography); ?>', sans-serif !important;
        }
        <?php if (isset($_GET['embedded']) || isset($_GET['portal'])): ?>
        .sidebar, .sidebar-wrapper, .top-header, .app-header-admin, header:not(.embedded-header) { display: none !important; }
        .app-container, .main-content, .content-wrapper, .container-fluid, container, main, .main-layout { margin-left: 0 !important; margin-right: 0 !important; width: 100% !important; max-width: 100% !important; padding: 12px !important; box-sizing: border-box !important; }
        html, body { background-color: #f8fafc !important; color: #0f172a !important; overflow-y: auto !important; height: auto !important; display: block !important; margin: 0 !important; padding: 0 !important; }
        body.dark-theme { background-color: #0f172a !important; color: #f8fafc !important; }
        <?php endif; ?>
    </style>
    <script>
        window.AppConfig = {
            toastPosition: '<?php echo htmlspecialchars($globalSettings['toast_position'] ?? "top-right"); ?>',
            toastStyle: '<?php echo htmlspecialchars($globalSettings['toast_style'] ?? "card"); ?>'
        };
        window.appSettings = <?php echo json_encode($globalSettings); ?>;

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

        // CSRF XHR Interceptor
        const originalXhrSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.send = function(body) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                try {
                    this.setRequestHeader('X-CSRF-Token', csrfToken);
                } catch(e) {}
                if (body instanceof FormData && !body.has('csrf_token')) {
                    body.append('csrf_token', csrfToken);
                }
            }
            return originalXhrSend.apply(this, arguments);
        };
    </script>
</head>
<body>
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.body.classList.add('dark-theme');
                }
            } catch (e) {}
        })();
    </script>
    <div class="app-container">
