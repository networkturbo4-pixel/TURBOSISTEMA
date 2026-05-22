$sourceDir = "c:\xampp\htdocs\TURBOSAAS"
$deployDir = "c:\xampp\htdocs\TURBOSAAS_DEPLOY_TEMP"
$zipPath = "c:\xampp\htdocs\TURBOSAAS\turbosaas_produccion.zip"

# Limpiar directorio temporal si existe
if (Test-Path $deployDir) {
    Remove-Item -Path $deployDir -Recurse -Force
}
New-Item -ItemType Directory -Path $deployDir | Out-Null

# Copiar todo al directorio temporal
Copy-Item -Path "$sourceDir\*" -Destination $deployDir -Recurse -Force

# Eliminar archivos/carpetas que no van a producción
$itemsToRemove = @(
    "$deployDir\.claude",
    "$deployDir\.gitignore",
    "$deployDir\config\env.php",
    "$deployDir\build_deploy.ps1",
    "$deployDir\turbosaas_produccion.zip"
)

foreach ($item in $itemsToRemove) {
    if (Test-Path $item) {
        Remove-Item -Path $item -Recurse -Force
    }
}

# Limpiar el contenido de sessions y uploads, pero dejar las carpetas
$foldersToClean = @(
    "$deployDir\sessions",
    "$deployDir\uploads"
)

foreach ($folder in $foldersToClean) {
    if (Test-Path $folder) {
        Remove-Item -Path "$folder\*" -Recurse -Force
    }
}

# Crear el archivo zip
if (Test-Path $zipPath) {
    Remove-Item -Path $zipPath -Force
}
Compress-Archive -Path "$deployDir\*" -DestinationPath $zipPath -Force

# Limpiar directorio temporal
Remove-Item -Path $deployDir -Recurse -Force

Write-Host "Archivo de despliegue creado exitosamente en: $zipPath"
