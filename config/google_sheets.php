<?php
/**
 * Google Sheets Integration — Configuración
 * 
 * 1. Descarga el JSON de credenciales de tu Service Account en Google Cloud
 * 2. Coloca el archivo JSON en c:\xampp\htdocs\TURBOSAAS\config\
 * 3. Actualiza CREDENTIALS_PATH con el nombre del archivo
 * 4. Actualiza SPREADSHEET_ID con el ID de tu Google Sheet
 *    (lo encuentras en la URL: sheets.google.com/d/[ESTE_ID]/edit)
 */

define('GSHEETS_CREDENTIALS_PATH', __DIR__ . '/google-credentials.json');
define('GSHEETS_SPREADSHEET_ID', '1hR0SON6jAB1Z1KBg_m4DzxqSdXj1mpmWCJzPPV5CJF0');   // ← Pega aquí el ID de tu Google Sheet
define('GSHEETS_APPLICATION_NAME', 'TurboSaaS Inventario');

// Nombres de las hojas dentro del Spreadsheet
define('GSHEETS_TAB_PRODUCTS',  'Productos');
define('GSHEETS_TAB_SKUS',      'Control de Stock');
define('GSHEETS_TAB_SUMMARY',   'Resumen');
