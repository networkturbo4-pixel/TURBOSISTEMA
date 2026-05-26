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
    <title><?php echo htmlspecialchars($appName); ?></title>
    <?php if ($favicon): ?>
    <link rel="icon" href="<?php echo BASE_URL . '/' . $favicon; ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
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
    </style>
    <script>
        window.AppConfig = {
            toastPosition: '<?php echo htmlspecialchars($globalSettings['toast_position'] ?? "top-right"); ?>',
            toastStyle: '<?php echo htmlspecialchars($globalSettings['toast_style'] ?? "card"); ?>'
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
