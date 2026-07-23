<?php
/**
 * Google Drive Integration — Configuración Global
 * 
 * 1. Asegúrate de que el archivo google-credentials.json exista en la carpeta config/
 * 2. Comparte la carpeta raíz de tu Google Drive con el correo del Service Account:
 *    sistematurbo@hazel-logic-477416-m7.iam.gserviceaccount.com (Permiso de Editor)
 * 3. Copia el ID de la carpeta compartida en GDRIVE_ROOT_FOLDER_ID a continuación.
 *    (El ID se encuentra en la URL de la carpeta: drive.google.com/drive/folders/[ESTE_ID])
 */

if (!defined('GDRIVE_CREDENTIALS_PATH')) {
    define('GDRIVE_CREDENTIALS_PATH', __DIR__ . '/google-credentials.json');
}

// ID de la carpeta raíz principal en Google Drive (Pega aquí el ID de tu carpeta compartida)
if (!defined('GDRIVE_ROOT_FOLDER_ID')) {
    define('GDRIVE_ROOT_FOLDER_ID', '19bDGSKJQGY1yMQksotPwfAzBgeYhCzn1');
}

// Método de Autenticación: 'service_account' o 'oauth2'
if (!defined('GDRIVE_AUTH_MODE')) {
    define('GDRIVE_AUTH_MODE', 'oauth2'); // Configurado en 'oauth2' para cuentas personales (@gmail.com)
}

// Credenciales OAuth 2.0 (Necesario para cuentas personales @gmail.com para evitar el límite 0MB del Service Account)
if (!defined('GDRIVE_CLIENT_ID')) {
    define('GDRIVE_CLIENT_ID', '1074375682319-uq1b11cno0ef7dtnnro80jc5du02p66r.apps.googleusercontent.com');
}
if (!defined('GDRIVE_CLIENT_SECRET')) {
    define('GDRIVE_CLIENT_SECRET', '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');
}
if (!defined('GDRIVE_REFRESH_TOKEN')) {
    define('GDRIVE_REFRESH_TOKEN', '1//0ht5eaOrakSTlCgYIARAAGBESNwF-L9IrrpXibH1Srj5Lx2Z5wz26pFBEfUWH6Tympa_OcrhjyjzScHZH9Ub5jA1O53zTD7PlM5w');
}

// Configuración predeterminada de visibilidad de los archivos subidos
if (!defined('GDRIVE_MAKE_PUBLIC_DEFAULT')) {
    define('GDRIVE_MAKE_PUBLIC_DEFAULT', true);
}

// Aplicación / servicio
if (!defined('GDRIVE_APPLICATION_NAME')) {
    define('GDRIVE_APPLICATION_NAME', 'TurboSaaS Drive Storage');
}
