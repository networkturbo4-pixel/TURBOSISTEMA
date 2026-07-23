# 🚀 Base del Sistema: TurboSaaS

Este documento sirve como la **fuente de la verdad** para el desarrollo continuo de TurboSaaS. Define los estándares de diseño, arquitectura del frontend y backend, y componentes reutilizables para mantener la consistencia en todos los módulos.

---

## 🛠️ Stack Tecnológico
*   **Backend:** PHP Nativo (PDO para la Base de Datos).
*   **Base de Datos:** MySQL / MariaDB.
*   **Frontend (Lógica):** JavaScript puro (Vanilla JS) usando `fetch` para peticiones AJAX.
*   **Estilos:** CSS Custom (Variables CSS) + Bootstrap 5 (Únicamente para utilidades de Grid, Spacing y Display).
*   **Iconografía:** Phosphor Icons.

---

## 🎨 Guía de Estilos y UI/UX (App-like Design)

El diseño del sistema simula una **Aplicación Web Moderna (SaaS)** con esquemas limpios, bordes redondeados, sombras suaves y soporte completo para Modo Claro y Oscuro.

### 1. Variables CSS Globales (`:root` en `style.css`)
El sistema está construido alrededor de variables inyectadas desde la base de datos (pestaña Apariencia).
*   `--bg-color`: Fondo general de la aplicación.
*   `--surface-color`: Fondo de las tarjetas y elementos elevados (suele ser blanco en claro y gris muy oscuro en oscuro).
*   `--text-color` y `--text-muted`: Colores de tipografía principal y secundaria.
*   `--primary-color`: Color de acento para botones, checks y focos. Se inyecta dinámicamente desde la base de datos (Color de Énfasis Claro/Oscuro) dependiendo del tema actual (`body.dark-theme`).

### 2. Componentes Reutilizables

Al crear nuevos módulos, **debes utilizar las siguientes clases CSS** para mantener la estética:

#### Cabecera de Página (`.page-header-card`)
Usado en la parte superior de todos los módulos.
```html
<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="ph ph-gear"></i></div>
        <div class="page-header-info">
            <h2>Título</h2>
            <p>Subtítulo descriptivo</p>
        </div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary">Acción Principal</button>
    </div>
</div>
```
**Nota sobre Móviles (FAB):** En pantallas pequeñas (`max-width: 768px`), cualquier botón `.btn-primary` dentro de `.page-header-actions` se convertirá automáticamente en un **Botón Flotante Fijo (FAB)** en la esquina inferior derecha para asegurar que el usuario siempre pueda guardar o ejecutar la acción principal sin hacer scroll hacia arriba. El contenedor padre y el fondo del sistema se mantienen limpios (sin espacios en blanco forzados), por lo que el botón flota de manera totalmente libre sobre el final del contenido.

#### Contenedor de Formulario / Tarjetas (`.settings-section`)
Contenedor principal blanco (o gris en dark mode) con borde suave de 16px y sombra.
```html
<div class="settings-section">
    <!-- Contenido del módulo -->
</div>
```

#### Formularios Modernos (`.form-control`, `.form-select`)
Inputs diseñados para alto contraste en modo claro (`#f8fafc`) y oscuro (`#2a2a2a`). Tienen un padding amplio (`12px 16px`), bordes suaves de 10px y un anillo brillante (glow) azul al hacer `:focus`.

**Importante:** Cualquier input que tenga el atributo HTML `required` mostrará automáticamente un asterisco rojo (`*`) en su respectivo `<label>` superior.

#### Zonas de Subida de Archivos (Drag & Drop)
Para implementar una zona de subida de archivos interactiva, **no necesitas clases adicionales**. Simplemente usa un `<input type="file">`. El sistema (mediante `app.js`) lo detectará y lo envolverá automáticamente en una caja punteada moderna (`.file-drop-area`) con soporte para arrastrar y soltar.

#### Botones y Etiquetas
- **`.btn-primary`:** Botón oscuro y sólido (`#111827`) que "flota" al hacer hover.
- **`.btn-secondary`:** Botón secundario de fondo transparente con un borde sutil, usado para "Cancelar" o acciones menores.
- **`.tag-pill`:** Crea una etiqueta pequeña con fondo blanco y sombra suave. Ideal para categorizaciones. Opcionalmente puedes anidar un `<i>` (Phosphor Icon) para simular un botón de cerrar `x`.

#### Modales (Clean Cards)
Los modales (`.modal-content`) ahora utilizan un diseño limpio tipo "Clean Card" con una amplia sombra (`box-shadow: 0 12px 40px`). La cabecera (`.modal-header`) se integra sin bordes al cuerpo para mayor elegancia. Usa `<button class="close-modal">&times;</button>` para el botón de cerrar.

#### Tablas y Responsividad (`.table-responsive`)
Para mostrar listas de datos, utiliza la estructura estándar combinada con `.table-responsive`. Las tablas tienen un diseño premium de filas amplias, con cabeceras gris claro (`#f8fafc`).
```html
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td data-label="Nombre">Cesar</td>
                <td data-label="Rol"><span class="table-badge-dark">Admin</span></td>
                <td data-label="Acciones"><button class="table-btn-danger">Eliminar</button></td>
            </tr>
        </tbody>
    </table>
</div>
```
**Regla de Oro en Tablas:** Es **obligatorio** añadir el atributo `data-label="Nombre de Columna"` a cada `<td>`. En pantallas móviles, la tabla se transforma en "tarjetas" y CSS utilizará este `data-label` para mostrar el nombre del campo a la izquierda del valor.

**Clases Auxiliares de Tabla:**
- `.table-badge-dark`: Badge oscuro redondeado (`#111827`) para destacar roles o estados.
- `.table-btn-danger`: Botón pequeño rojo/rosado (`#f43f5e`) para acciones como eliminar.

#### Notificaciones Globales (Toasts)
En lugar de usar `alert()`, en cualquier parte del sistema JS (frontend) puedes llamar a:
```javascript
window.showToast("Mensaje de éxito", "success");
// o
window.showToast("Ocurrió un error", "error");
```
El diseño (posición y estilo visual) se determina dinámicamente según la preferencia guardada por el administrador en Configuración > Apariencia.

#### Pestañas (Tabs) tipo "Pill" (`.nav-tabs`)
Navegación horizontal encapsulada en un contenedor gris.
```html
<ul class="nav nav-tabs app-tabs">
    <li class="nav-item">
        <button class="nav-link active">Tab 1</button>
    </li>
</ul>
```

---

## ⚙️ Arquitectura del Sistema

### Rutas y Carpetas Principales
*   `/includes/`: Archivos globales de estructura (`header.php`, `sidebar.php`, `footer.php`, `modals.php`).
*   `/modules/`: Aquí vive cada vista del sistema (ej. `/modules/settings/index.php`).
*   `/ajax/`: Endpoints que reciben peticiones POST/GET y responden **estrictamente en formato JSON**.

### Estructura de un Endpoint AJAX
Cualquier archivo nuevo en `/ajax/` debe seguir esta convención:
```php
<?php
require_once '../config/db.php';
header('Content-Type: application/json');

// 1. Verificación de Seguridad
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 2. Lógica por Acción
if ($action === 'list') {
    // Consulta a DB...
    echo json_encode(['success' => true, 'data' => $resultados]);
    exit;
}

// 3. Fallback
echo json_encode(['success' => false, 'message' => 'Acción no válida']);
```

---

## 🔐 Seguridad y Autenticación
1.  **Doble Método de Ingreso:** Los usuarios pueden iniciar sesión con `email` + `password` o mediante un `PIN` de 4-6 dígitos único por usuario.
2.  **RBAC (Roles y Permisos):** Cada usuario tiene asignado un Rol (ej. "admin", "vendedor"). El acceso a módulos específicos se valida leyendo el rol de la sesión.

---

## 🌙 Modo Oscuro (`body.dark-theme`)
*   Se activa mediante un switch en forma de **píldora dual** ubicado en la parte inferior del **Sidebar**.
*   Inyecta la clase `dark-theme` al `<body>`.
*   Cambia automáticamente las imágenes que tengan la clase `.logo-light` a `.logo-dark`.
*   Todos los inputs, tarjetas y tablas dentro de la app tienen reglas específicas bajo `body.dark-theme` para invertir sus colores de manera elegante y legible sin requerir clases adicionales.

---

## 🧭 Sidebar y Navegación
*   **Submenús Colapsables:** El sidebar soporta elementos con submenús. Se usa un contenedor `.sidebar-item.has-submenu` junto con `.sidebar-toggle` (que contiene el texto y la flecha `.toggle-icon`) y un contenedor `.sidebar-submenu` para los enlaces anidados.
*   **Badges de Sidebar:** Se pueden añadir notificaciones visuales a los enlaces usando clases como `.sidebar-badge.badge-green` o `.badge-orange`.
*   **Comportamiento Móvil:** En dispositivos móviles, el sidebar se oculta fuera de la pantalla. Al hacer clic en el botón de hamburguesa, se despliega. Si el usuario **hace clic fuera del sidebar**, este se cerrará automáticamente. El logo se mantiene visible en la parte superior del sidebar durante la vista móvil.

---

## 📁 Almacenamiento en Google Drive API

El sistema cuenta con un helper centralizado para subir y almacenar imágenes, videos y documentos en **Google Drive** desde cualquier módulo del sistema.

### 1. Archivo de Configuración (`/config/google_drive.php`)
- `GDRIVE_CREDENTIALS_PATH`: Ruta al archivo JSON del Service Account (`google-credentials.json`).
- `GDRIVE_ROOT_FOLDER_ID`: ID de la carpeta raíz compartida en Google Drive.
- `GDRIVE_MAKE_PUBLIC_DEFAULT`: (`true`) Todos los archivos subidos tienen permiso de lectura público por enlace.

### 2. Uso desde PHP (`GoogleDriveHelper.php`)
```php
require_once __DIR__ . '/../includes/GoogleDriveHelper.php';

// Subir desde array $_FILES (ej. $_FILES['archivo']) a subcarpeta 'Inventario'
$res = GoogleDriveHelper::uploadFromUploadedFile($_FILES['archivo'], 'Inventario');

if ($res['success']) {
    $fileId    = $res['file_id'];
    $directUrl = $res['direct_link']; // https://lh3.googleusercontent.com/d/[ID]
    $viewUrl   = $res['web_view_link'];
}
```

### 3. Subida directa vía AJAX (`/ajax/upload_drive.php`)
Puedes enviar FormData con la clave `file` o `archivo` y el nombre de la subcarpeta `subfolder`:
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('subfolder', 'Productos');

fetch('/ajax/upload_drive.php', { method: 'POST', body: formData })
  .then(res => res.json())
  .then(data => {
      if(data.success) {
          console.log("URL de la imagen en Google Drive:", data.direct_link);
      }
  });
```

