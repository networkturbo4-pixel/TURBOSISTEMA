<?php
require_once '../../config/db.php';
$folio = $_GET['folio'] ?? null;
$token = $_GET['token'] ?? null;

if (!$folio) {
    echo "Folio no especificado";
    exit;
}

// Fetch Acta and joined technical details
$stmt = $pdo->prepare("SELECT a.*, tech.name as tech_name 
                      FROM actas a 
                      LEFT JOIN users tech ON a.tecnico_id = tech.id 
                      WHERE a.prefijo = ? AND a.folio = ? AND a.token = ?");
// Dividimos LIM- y el numero (LIM-000001 -> LIM- y 000001) si viene junto, pero en DB se guardan por separado.
// Si el parametro viene como LIM-000001:
$prefijo = 'LIM-';
$folioNum = $folio;
if (strpos($folio, 'LIM-') === 0) {
    $folioNum = substr($folio, 4);
}

$stmt->execute([$prefijo, $folioNum, $token]);
$acta = $stmt->fetch();

if (!$acta) {
    echo "Acta no encontrada o enlace inválido.";
    exit;
}

// Obtener Configuraciones Globales de TurboSaaS
$stmtConfig = $pdo->query("SELECT setting_key, setting_value FROM settings");
$config = [];
while($row = $stmtConfig->fetch()) {
    $config[$row['setting_key']] = $row['setting_value'];
}

$logo = !empty($config['logo_light']) ? '../../' . $config['logo_light'] : '';
$appName = $config['app_name'] ?? 'TurboSaaS';
$primaryColor = $config['primary_color_light'] ?? '#4361ee'; // Default primary

// Obtener Equipos, Materiales y Fotos vinculadas
$equipos = $pdo->prepare("SELECT * FROM actas_equipos WHERE acta_id = ?");
$equipos->execute([$acta['id']]);
$equiposList = $equipos->fetchAll();

$materiales = $pdo->prepare("SELECT * FROM actas_materiales WHERE acta_id = ?");
$materiales->execute([$acta['id']]);
$materialesList = $materiales->fetchAll();

$fotos = $pdo->prepare("SELECT * FROM actas_fotos WHERE acta_id = ?");
$fotos->execute([$acta['id']]);
$fotosList = $fotos->fetchAll();

function getFoto($fotosList, $tipo) {
    foreach($fotosList as $f) {
        if ($f['tipo'] === $tipo) return '../../' . $f['ruta_archivo'];
    }
    return '';
}

$routerImg = getFoto($fotosList, 'router');
$fachadaImg = getFoto($fotosList, 'fachada');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acta <?= htmlspecialchars($acta['prefijo'].$acta['folio']) ?> - <?= htmlspecialchars($appName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --primary: <?= $primaryColor ?>;
            --primary-soft: <?= $primaryColor ?>15;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 0;
            font-size: 14px;
            color: var(--text-main);
            line-height: 1.5;
        }

        .page-wrapper {
            padding: 40px 20px;
            display: flex;
            justify-content: center;
        }

        .page-container {
            background: white;
            width: 850px;
            max-width: 100%;
            padding: 50px;
            box-shadow: var(--card-shadow);
            border-radius: 12px;
            position: relative;
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .page-wrapper { padding: 10px; }
            .page-container { padding: 25px 15px; border-radius: 0; }
            .header-grid { grid-template-columns: 1fr !important; gap: 20px; text-align: center; }
            .brand-info { align-items: center !important; }
            .folio-badge { text-align: center !important; }
            .grid-2col { grid-template-columns: 1fr !important; }
            .signatures { flex-direction: column !important; gap: 40px; }
            .evidence-grid { grid-template-columns: 1fr !important; }
        }

        /* Header */
        .header-grid {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 30px;
            margin-bottom: 35px;
        }

        .brand-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .brand-logo {
            max-height: 60px;
            margin-bottom: 8px;
        }

        .company-name {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .main-title {
            text-align: center;
        }

        .main-title h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-main);
            margin: 0;
        }

        .main-title .subtitle {
            font-size: 0.85rem;
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
        }

        .folio-badge {
            text-align: right;
        }

        .folio-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .folio-number {
            font-size: 1.8rem;
            font-weight: 900;
            color: #ef4444;
            font-family: 'Consolas', monospace;
        }

        /* Sections */
        .section {
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
        }

        .section-header i {
            width: 32px;
            height: 32px;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 1.2rem;
        }

        .section-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-main);
            text-transform: uppercase;
        }

        /* Grid */
        .grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .info-card {
            background: var(--bg-light);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .info-row {
            margin-bottom: 12px;
        }
        .info-row:last-child { margin-bottom: 0; }

        .info-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }

        .info-value {
            font-weight: 500;
            color: var(--text-main);
            font-size: 0.95rem;
        }

        /* Tables */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
        }

        .modern-table th {
            background: var(--bg-light);
            padding: 10px 15px;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            border-bottom: 1px solid var(--border-color);
        }

        .modern-table td {
            padding: 10px 15px;
            border-bottom: 1px solid var(--border-color);
            font-weight: 500;
        }
        .modern-table tr:last-child td { border-bottom: none; }

        /* Configuration area */
        .config-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            background: var(--primary-soft);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--primary)30;
        }

        /* Evidence */
        .evidence-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .evidence-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .evidence-img-container {
            width: 100%;
            height: 220px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .evidence-img-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .evidence-footer {
            padding: 10px;
            text-align: center;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            background: var(--bg-light);
            border-top: 1px solid var(--border-color);
        }

        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            gap: 50px;
        }

        .sig-box {
            flex: 1;
            text-align: center;
        }

        .sig-canvas {
            height: 100px;
            border-bottom: 2px solid var(--text-main);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fafafa;
            border-radius: 8px 8px 0 0;
        }

        .sig-img {
            max-height: 90px;
            max-width: 100%;
            mix-blend-mode: multiply;
        }

        .sig-name {
            font-weight: 700;
            color: var(--text-main);
            font-size: 0.9rem;
            display: block;
        }

        .sig-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        /* Footer */
        .footer-note {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Floating Controls */
        .floating-controls {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            gap: 15px;
            z-index: 1000;
        }

        .ctrl-btn {
            height: 54px;
            padding: 0 25px;
            border-radius: 27px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            color: white;
            transition: transform 0.2s;
        }
        .ctrl-btn:hover { transform: translateY(-3px); }
        .btn-pdf { background: #ef4444; }
        .btn-print { background: #475569; }

        @media print {
            body { background: white; padding: 0 !important; }
            .page-wrapper { padding: 0 !important; }
            .page-container { box-shadow: none !important; width: 100% !important; padding: 0 !important; border-radius: 0 !important; }
            .floating-controls, .no-print { display: none !important; }
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body>

<div class="no-print floating-controls">
    <button class="ctrl-btn btn-pdf" onclick="downloadPDF()">
        <i class="ph ph-file-pdf"></i> Guardar PDF
    </button>
    <button class="ctrl-btn btn-print" onclick="window.print()">
        <i class="ph ph-printer"></i> Imprimir
    </button>
</div>

<div class="page-wrapper">
    <div class="page-container" id="pdfContent">
        
        <header class="header-grid">
            <div class="brand-info">
                <?php if($logo): ?>
                    <img src="<?= htmlspecialchars($logo) ?>" class="brand-logo" alt="Logo">
                <?php endif; ?>
                <span class="company-name"><?= htmlspecialchars($appName) ?></span>
            </div>
            
            <div class="main-title">
                <span class="subtitle">Documento Oficial</span>
                <h1>ACTA DE CONFORMIDAD</h1>
                <div style="margin-top: 8px;">
                    <span class="status-badge"><?= htmlspecialchars($acta['srv_estado'] ?? 'Finalizada') ?></span>
                </div>
            </div>

            <div class="folio-badge">
                <span class="folio-label">NÚMERO DE CONTROL</span>
                <span class="folio-number">#<?= htmlspecialchars($acta['prefijo'].$acta['folio']) ?></span>
            </div>
        </header>

        <section class="section">
            <div class="section-header">
                <i class="ph ph-user"></i>
                <h3>1. Información del Cliente</h3>
            </div>
            <div class="info-card">
                <div class="grid-2col">
                    <div>
                        <div class="info-row">
                            <span class="info-label">Cliente / Razón Social</span>
                            <span class="info-value"><?= htmlspecialchars($acta['cliente_nombre']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Dirección de Instalación</span>
                            <span class="info-value"><?= htmlspecialchars($acta['cliente_direccion']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Distrito y Referencia</span>
                            <span class="info-value"><?= htmlspecialchars($acta['cliente_distrito'] ?? '-') ?> | <?= htmlspecialchars($acta['cliente_referencia'] ?? '-') ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="info-row">
                            <span class="info-label">DNI / RUC | Rotulado QR</span>
                            <span class="info-value"><?= htmlspecialchars($acta['cliente_dni_ruc'] ?? '-') ?> | <?= htmlspecialchars($acta['cliente_rotulado'] ?? '-') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Contacto (WhatsApp / Alt)</span>
                            <span class="info-value"><?= htmlspecialchars($acta['cliente_whatsapp']) ?> <?= !empty($acta['cliente_celular_alt']) ? ' / '.$acta['cliente_celular_alt'] : '' ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Geolocalización GPS</span>
                            <span class="info-value" style="font-family: monospace; font-size: 0.85rem;">Lat: <?= htmlspecialchars($acta['cliente_gps_lat'] ?? '-') ?> Lng: <?= htmlspecialchars($acta['cliente_gps_lng'] ?? '-') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid-2col section">
            <section>
                <div class="section-header">
                    <i class="ph ph-plugs"></i>
                    <h3>2. Planta Externa</h3>
                </div>
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Nodo & Puerto</span>
                        <span class="info-value"><?= htmlspecialchars($acta['pe_nodo'] ?? '-') ?> / Pto: <?= htmlspecialchars($acta['pe_puerto'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">NAP / Caja de Distribución</span>
                        <span class="info-value"><?= htmlspecialchars($acta['pe_nap'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Potencia Óptica Recibida</span>
                        <span class="info-value" style="color: var(--primary); font-weight: 700;"><?= htmlspecialchars($acta['pe_potencia'] ?? '-') ?> dBm</span>
                    </div>
                </div>
            </section>

            <section>
                <div class="section-header">
                    <i class="ph ph-calendar-check"></i>
                    <h3>3. Detalle de Servicio</h3>
                </div>
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Fecha de Ejecución</span>
                        <span class="info-value"><?= htmlspecialchars($acta['srv_fecha'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Rango Horario</span>
                        <span class="info-value"><?= htmlspecialchars($acta['srv_hora_inicio'] ?? '-') ?> - <?= htmlspecialchars($acta['srv_hora_fin'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Técnico Responsable</span>
                        <span class="info-value" style="color: var(--primary); font-weight: 700;"><?= htmlspecialchars($acta['tech_name'] ?? 'No asignado') ?></span>
                    </div>
                </div>
            </section>
        </div>

        <section class="section">
            <div class="section-header">
                <i class="ph ph-package"></i>
                <h3>4. Equipos & Materiales</h3>
            </div>
            
            <div class="grid-2col">
                <div>
                    <h4 style="margin: 0 0 10px 0; font-size: 0.8rem; color: var(--text-muted);">EQUIPOS INSTALADOS Y RETIRADOS</h4>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Marca/Mod</th>
                                <th>Acción</th>
                                <th>Serie / MAC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($equiposList)): ?>
                                <tr><td colspan="3" style="text-align:center; color:#94a3b8; font-style:italic;">Sin equipos registrados</td></tr>
                            <?php else: ?>
                                <?php foreach ($equiposList as $eq): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($eq['modelo_marca'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($eq['accion'] ?? '-') ?></td>
                                        <td style="font-family:monospace; font-size: 0.85rem;"><?= htmlspecialchars($eq['serie_mac'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h4 style="margin: 0 0 10px 0; font-size: 0.8rem; color: var(--text-muted);">CONSUMO DE MATERIALES</h4>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Acción</th>
                                <th>Descripción</th>
                                <th style="text-align:center;">Ctd.</th>
                                <th>Propiedad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($materialesList)): ?>
                                <tr><td colspan="4" style="text-align:center; color:#94a3b8; font-style:italic;">Sin materiales registrados</td></tr>
                            <?php else: ?>
                                <?php foreach ($materialesList as $mat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($mat['accion'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($mat['descripcion'] ?? '-') ?></td>
                                        <td style="text-align:center;"><?= htmlspecialchars($mat['cantidad'] ?? '-') ?> <?= htmlspecialchars($mat['unidad'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($mat['propiedad'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-header">
                <i class="ph ph-wifi-high"></i>
                <h3>5. Configuración de Red / TV</h3>
            </div>
            <div class="config-box">
                <div>
                    <span class="info-label" style="color:var(--primary);">WiFi SSID Red</span>
                    <span class="info-value" style="font-weight: 700;"><?= htmlspecialchars($acta['red_ssid'] ?? '-') ?></span>
                </div>
                <div>
                    <span class="info-label" style="color:var(--primary);">Contraseña WiFi</span>
                    <span class="info-value" style="font-family: monospace;"><?= htmlspecialchars($acta['red_password'] ?? '-') ?></span>
                </div>
                <div>
                    <span class="info-label" style="color:var(--primary);">Test Velocidad</span>
                    <span class="info-value">↓ <?= htmlspecialchars($acta['red_speed_dl'] ?? '0') ?> / ↑ <?= htmlspecialchars($acta['red_speed_ul'] ?? '0') ?> Mbps</span>
                </div>
            </div>
        </section>

        <section class="section" style="page-break-inside: avoid;">
            <div class="section-header">
                <i class="ph ph-camera"></i>
                <h3>6. Registro Fotográfico y Observaciones</h3>
            </div>
            
            <div style="background: var(--bg-light); padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #cbd5e1;">
                <span class="info-label">Observaciones Técnicas</span>
                <p style="margin: 5px 0 0 0; color: var(--text-main);">
                    <?= !empty($acta['observaciones']) ? nl2br(htmlspecialchars($acta['observaciones'])) : 'El servicio se realizó sin observaciones adicionales.' ?>
                </p>
            </div>

            <div class="evidence-grid">
                <div class="evidence-card">
                    <div class="evidence-img-container">
                        <?php if (!empty($routerImg)): ?>
                            <img src="<?= htmlspecialchars($routerImg) ?>">
                        <?php else: ?><i class="ph ph-image" style="font-size: 3rem; color: #cbd5e1;"></i><?php endif; ?>
                    </div>
                    <div class="evidence-footer">INSTALACIÓN INTERNA (ROUTER)</div>
                </div>
                <div class="evidence-card">
                    <div class="evidence-img-container">
                        <?php if (!empty($fachadaImg)): ?>
                            <img src="<?= htmlspecialchars($fachadaImg) ?>">
                        <?php else: ?><i class="ph ph-image" style="font-size: 3rem; color: #cbd5e1;"></i><?php endif; ?>
                    </div>
                    <div class="evidence-footer">INSTALACIÓN EXTERNA (FACHADA)</div>
                </div>
            </div>
        </section>

        <section class="signatures" style="page-break-inside: avoid;">
            <div class="sig-box">
                <div class="sig-canvas">
                    <?php if (!empty($acta['firma_cliente'])): ?>
                        <img src="<?= htmlspecialchars($acta['firma_cliente']) ?>" class="sig-img">
                    <?php else: ?>
                        <span style="color: #cbd5e1; font-style: italic;">Sin firma</span>
                    <?php endif; ?>
                </div>
                <span class="sig-name"><?= htmlspecialchars($acta['cliente_nombre']) ?></span>
                <span class="sig-label">Firma del Cliente / Titular</span>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 4px;">DNI: <?= htmlspecialchars($acta['cliente_dni_ruc'] ?? '-') ?></div>
            </div>
            
            <div class="sig-box">
                <div class="sig-canvas">
                    <?php if (!empty($acta['firma_tecnico'])): ?>
                        <img src="<?= htmlspecialchars($acta['firma_tecnico']) ?>" class="sig-img">
                    <?php else: ?>
                        <span style="color: #cbd5e1; font-style: italic;">Sin firma</span>
                    <?php endif; ?>
                </div>
                <span class="sig-name"><?= htmlspecialchars($acta['tech_name'] ?? 'Técnico Responsable') ?></span>
                <span class="sig-label">Firma del Técnico Autorizado</span>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 4px;"><?= htmlspecialchars($appName) ?> - OPERACIONES</div>
            </div>
        </section>

        <footer class="footer-note">
            <p>Este documento constituye una declaración de conformidad del cliente con el servicio prestado.</p>
            <p style="font-weight: 600; font-size: 0.65rem;">GENERADO DIGITALMENTE POR <?= mb_strtoupper($appName) ?> - <?= date('d/m/Y H:i') ?></p>
        </footer>
    </div>
</div>

<script>
    function downloadPDF() {
        const element = document.getElementById('pdfContent');
        const opt = {
            margin: [10, 0, 10, 0],
            filename: 'Acta_<?= htmlspecialchars($acta['prefijo'].$acta['folio']) ?>_<?= str_replace(' ', '_', $acta['cliente_nombre']) ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { 
                scale: 2, 
                useCORS: true, 
                logging: false,
                letterRendering: true
            },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        
        const btn = document.querySelector('.btn-pdf');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Generando...';
        btn.style.opacity = '0.7';
        
        html2pdf().set(opt).from(element).save().then(() => {
            btn.innerHTML = orig;
            btn.style.opacity = '1';
        });
    }

    if (new URLSearchParams(window.location.search).get('pdf') === '1') {
        setTimeout(downloadPDF, 1500);
    }
</script>

</body>
</html>
