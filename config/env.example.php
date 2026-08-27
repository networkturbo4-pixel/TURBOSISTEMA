<?php
// Ejemplo de configuración de credenciales y variables de entorno para producción
// Copia este archivo como env.php y configura tus valores reales

// Credenciales de Base de Datos en Producción
define('DB_HOST', 'localhost');
define('DB_NAME', 'tu_base_de_datos_cpanel');
define('DB_USER', 'tu_usuario_cpanel');
define('DB_PASS', 'tu_contrasena_cpanel');

// Token de seguridad
define('JSON_PE_TOKEN', 'tu_token_de_produccion');

// Configuración de Google Drive en Producción (OAuth 2.0)
define('GDRIVE_AUTH_MODE', 'oauth2');
define('GDRIVE_ROOT_FOLDER_ID', 'ID_DE_LA_CARPETA_RAIZ_DE_DRIVE'); // ID de tu carpeta compartida en Drive
define('GDRIVE_CLIENT_ID', 'TU_CLIENT_ID_DE_GOOGLE_CONSOLE');
define('GDRIVE_CLIENT_SECRET', 'TU_CLIENT_SECRET_DE_GOOGLE_CONSOLE');
define('GDRIVE_REFRESH_TOKEN', 'EL_REFRESH_TOKEN_GENERADO'); // Se obtiene automáticamente con get_oauth_token.php
