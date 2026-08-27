// ==========================================================
// TURBOSAAS - CONTROL DE ACTIVOS EMPRESARIALES
// ==========================================================

const metaBase = document.querySelector('meta[name="base-url"]');
const baseUrl = (metaBase && typeof metaBase.content === 'string') 
    ? metaBase.content 
    : (typeof window.BASE_URL === 'string' ? window.BASE_URL : (window.location.pathname.includes('/TURBOSAAS') ? '/TURBOSAAS' : ''));

function appendCsrf(formData) {
    const csrf = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';
    if (csrf && !formData.has('csrf_token')) {
        formData.append('csrf_token', csrf);
    }
    return formData;
}

function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

let allVehicles = [];
let currentVehiculoId = null;
let currentSelectedCategory = 'vehiculo';
let isSavingAsset = false;

// ==========================================================
// CONFIGURACIÓN DINÁMICA DE CATEGORÍAS DE ACTIVOS
// ==========================================================
const ASSET_CATEGORIES = {
    vehiculo: {
        name: 'Vehículo',
        icon: 'ph-car-profile',
        badgeClass: 'badge-cat-vehiculo',
        defaultType: 'auto',
        renderFields: (catData = {}) => `
            <!-- Subtipo Visual Cards -->
            <div class="form-group-custom mb-3">
                <label class="form-label-custom">Tipo de Vehículo <span class="req">*</span></label>
                <input type="hidden" id="nvTipo" value="${catData.tipo || 'auto'}" required>
                <div class="type-selector-cards">
                    <div class="type-card-opt ${(!catData.tipo || catData.tipo==='auto') ? 'active':''}" data-type="auto" onclick="selectNewVehicleSubtype('auto', this)">
                        <div class="type-card-icon"><i class="ph-bold ph-car"></i></div>
                        <span>Auto</span>
                    </div>
                    <div class="type-card-opt ${catData.tipo==='camion' ? 'active':''}" data-type="camion" onclick="selectNewVehicleSubtype('camion', this)">
                        <div class="type-card-icon"><i class="ph-bold ph-truck"></i></div>
                        <span>Camión</span>
                    </div>
                    <div class="type-card-opt ${catData.tipo==='motocicleta' ? 'active':''}" data-type="motocicleta" onclick="selectNewVehicleSubtype('motocicleta', this)">
                        <div class="type-card-icon"><i class="ph-bold ph-motorcycle"></i></div>
                        <span>Moto</span>
                    </div>
                </div>
            </div>

            <!-- Placa y Nombre -->
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Placa de Rodaje <span class="req">*</span></label>
                        <div class="input-with-icon">
                            <i class="ph-bold ph-identification-badge"></i>
                            <input type="text" class="form-control text-uppercase font-monospace plate-input" id="nvPlaca" required placeholder="Ej: ABC-123" maxlength="15" value="${catData.placa || ''}">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Nombre / Alias del Vehículo</label>
                        <div class="input-with-icon">
                            <i class="ph-bold ph-car"></i>
                            <input type="text" class="form-control" id="nvNombre" placeholder="Ej: Camioneta Cuadrilla 1" value="${catData.nombre || ''}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Marca</label>
                        <div class="input-with-icon">
                            <i class="ph-bold ph-tag"></i>
                            <input type="text" class="form-control" id="nvMarca" placeholder="Ej: Toyota / Nissan / Honda" value="${catData.marca || ''}">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Modelo</label>
                        <div class="input-with-icon">
                            <i class="ph-bold ph-steering-wheel"></i>
                            <input type="text" class="form-control" id="nvModelo" placeholder="Ej: Hilux / Sentra / Wave" value="${catData.modelo || ''}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Año Fabricación</label>
                        <input type="number" class="form-control" id="nvExtraAnio" placeholder="Ej: 2024" min="1990" max="2030">
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Combustible</label>
                        <select class="form-select custom-select-filter" id="nvExtraCombustible">
                            <option value="Gasolina">Gasolina</option>
                            <option value="Diesel" selected>Diesel</option>
                            <option value="GLP/GNV">GLP / GNV</option>
                            <option value="Eléctrico">Eléctrico / Híbrido</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Kilometraje</label>
                        <input type="text" class="form-control" id="nvExtraKm" placeholder="Ej: 45,000 km">
                    </div>
                </div>
            </div>
        `
    },
    tecnologia: {
        name: 'Tecnología & Cómputo',
        icon: 'ph-desktop',
        badgeClass: 'badge-cat-tecnologia',
        defaultType: 'monitor',
        renderFields: (catData = {}) => `
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Tipo de Dispositivo / Equipo <span class="req">*</span></label>
                        <select class="form-select custom-select-filter" id="nvTipo">
                            <option value="monitor" selected>🖥️ Monitor / Pantalla</option>
                            <option value="laptop">💻 Laptop / Portátil</option>
                            <option value="desktop">🖥️ Computadora de Escritorio (PC)</option>
                            <option value="servidor">🖧 Servidor / Rack</option>
                            <option value="impresora">🖨️ Impresora / Multifuncional</option>
                            <option value="switch">🔀 Switch / Router / OLT / Fibra</option>
                            <option value="tablet">📱 Tablet / Teléfono Corporativo</option>
                            <option value="otro_tech">🔌 Otro Dispositivo</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Código Patrimonial / Serial (S/N) <span class="req">*</span></label>
                        <div class="input-with-icon">
                            <i class="ph-bold ph-barcode"></i>
                            <input type="text" class="form-control text-uppercase font-monospace plate-input" id="nvPlaca" required placeholder="Ej: SN-DELL-98214" maxlength="50" value="${catData.placa || ''}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Nombre / Descripción del Activo <span class="req">*</span></label>
                        <input type="text" class="form-control" id="nvNombre" required placeholder="Ej: Monitor Dell UltraSharp 27'' 4K / Laptop ThinkPad" value="${catData.nombre || ''}">
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Marca</label>
                        <input type="text" class="form-control" id="nvMarca" placeholder="Ej: Dell / HP / Lenovo" value="${catData.marca || ''}">
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Modelo</label>
                        <input type="text" class="form-control" id="nvModelo" placeholder="Ej: U2723QE / T14 Gen 3" value="${catData.modelo || ''}">
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Pulgadas / Pantalla</label>
                        <input type="text" class="form-control" id="nvExtraTech1" placeholder="Ej: 27'' IPS / 14'' FHD">
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Procesador / CPU</label>
                        <input type="text" class="form-control" id="nvExtraTech2" placeholder="Ej: Core i7-12700 / Ryzen 7">
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">RAM / Disco SSD</label>
                        <input type="text" class="form-control" id="nvExtraTech3" placeholder="Ej: 16GB RAM / 512GB SSD">
                    </div>
                </div>
            </div>
        `
    },
    mobiliario: {
        name: 'Mobiliario & Enseres',
        icon: 'ph-armchair',
        badgeClass: 'badge-cat-mobiliario',
        defaultType: 'escritorio',
        renderFields: (catData = {}) => `
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Tipo de Mobiliario <span class="req">*</span></label>
                        <select class="form-select custom-select-filter" id="nvTipo">
                            <option value="escritorio" selected>🪑 Escritorio / Mesa de Trabajo</option>
                            <option value="silla">🪑 Silla Ergonómica / Operativa</option>
                            <option value="archivador">🗄️ Archivador / Gavetero Metálico</option>
                            <option value="estante">📚 Estante / Anaquel / Librero</option>
                            <option value="mesa">🪵 Mesa de Reuniones / Directorio</option>
                            <option value="mueble">🛋️ Sillón / Juego de Muebles</option>
                            <option value="otro_mueble">📦 Otro Mobiliario</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Código Patrimonial / Etiqueta <span class="req">*</span></label>
                        <div class="input-with-icon">
                            <i class="ph-bold ph-barcode"></i>
                            <input type="text" class="form-control text-uppercase font-monospace plate-input" id="nvPlaca" required placeholder="Ej: MOB-0042" maxlength="50" value="${catData.placa || ''}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Nombre / Descripción <span class="req">*</span></label>
                        <input type="text" class="form-control" id="nvNombre" required placeholder="Ej: Escritorio Gerencial en L con Cajonera" value="${catData.nombre || ''}">
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Material Principal</label>
                        <input type="text" class="form-control" id="nvExtraMobMaterial" placeholder="Ej: Melamina 18mm / Madera / Metal">
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Dimensiones</label>
                        <input type="text" class="form-control" id="nvExtraMobDimensiones" placeholder="Ej: 75 x 140 x 60 cm">
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Color / Acabado</label>
                        <input type="text" class="form-control" id="nvExtraMobColor" placeholder="Ej: Cedro / Negro / Blanco">
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Condición Física</label>
                        <select class="form-select custom-select-filter" id="nvExtraMobCondicion">
                            <option value="Nuevo">✨ Nuevo</option>
                            <option value="Excelente" selected>👍 Excelente</option>
                            <option value="Bueno">👌 Bueno</option>
                            <option value="Regular">⚠️ Regular</option>
                        </select>
                    </div>
                </div>
            </div>
        `
    },
    herramientas: {
        name: 'Herramientas & Red',
        icon: 'ph-hammer',
        badgeClass: 'badge-cat-herramientas',
        defaultType: 'fusionadora',
        renderFields: (catData = {}) => `
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Tipo de Herramienta / Equipo <span class="req">*</span></label>
                        <select class="form-select custom-select-filter" id="nvTipo">
                            <option value="fusionadora" selected>⚡ Fusionadora de Fibra Óptica</option>
                            <option value="otdr">📶 OTDR / Medidor de Potencia / VFL</option>
                            <option value="escalera">🪜 Escalera Telescópica / Tijera</option>
                            <option value="taladro">🔨 Taladro / Rotomartillo / Amoladora</option>
                            <option value="generador">⚡ Generador Eléctrico / Grupo Electrógeno</option>
                            <option value="kit_herramientas">🧰 Maletín / Kit de Herramientas</option>
                            <option value="otra_herramienta">🛠️ Otra Herramienta</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Código / N° Serie <span class="req">*</span></label>
                        <div class="input-with-icon">
                            <i class="ph-bold ph-barcode"></i>
                            <input type="text" class="form-control text-uppercase font-monospace plate-input" id="nvPlaca" required placeholder="Ej: HR-FUS-001" maxlength="50" value="${catData.placa || ''}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Nombre / Descripción <span class="req">*</span></label>
                        <input type="text" class="form-control" id="nvNombre" required placeholder="Ej: Fusionadora Fujikura 90S con cortadora" value="${catData.nombre || ''}">
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Marca</label>
                        <input type="text" class="form-control" id="nvMarca" placeholder="Ej: Fujikura / DeWalt" value="${catData.marca || ''}">
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Modelo</label>
                        <input type="text" class="form-control" id="nvModelo" placeholder="Ej: 90S+ / DCD796" value="${catData.modelo || ''}">
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Próxima Calibración / Service</label>
                        <input type="date" class="form-control" id="nvExtraHerrCalibracion">
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Cuadrilla Asignada / Custodio</label>
                        <input type="text" class="form-control" id="nvExtraHerrCuadrilla" placeholder="Ej: Cuadrilla Fibra Óptica 1">
                    </div>
                </div>
            </div>
        `
    },
    otro: {
        name: 'Otro Activo',
        icon: 'ph-package',
        badgeClass: 'badge-cat-otro',
        defaultType: 'otro',
        renderFields: (catData = {}) => `
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Nombre / Descripción del Activo <span class="req">*</span></label>
                        <input type="text" class="form-control" id="nvNombre" required placeholder="Ej: Aire Acondicionado Split 18000 BTU" value="${catData.nombre || ''}">
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Código Patrimonial / Serial <span class="req">*</span></label>
                        <div class="input-with-icon">
                            <i class="ph-bold ph-barcode"></i>
                            <input type="text" class="form-control text-uppercase font-monospace plate-input" id="nvPlaca" required placeholder="Ej: ACT-0099" maxlength="50" value="${catData.placa || ''}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4 col-12">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Tipo de Activo</label>
                        <input type="text" class="form-control" id="nvTipo" placeholder="Ej: Climatización / Seguridad" value="otro">
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Marca</label>
                        <input type="text" class="form-control" id="nvMarca" placeholder="Ej: LG / Samsung" value="${catData.marca || ''}">
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Modelo</label>
                        <input type="text" class="form-control" id="nvModelo" placeholder="Ej: Inverter Dual" value="${catData.modelo || ''}">
                    </div>
                </div>
            </div>

            <div class="form-group-custom mb-3">
                <label class="form-label-custom">Observaciones / Especificaciones Adicionales</label>
                <textarea class="form-control" id="nvExtraOtroNotas" rows="2" placeholder="Detalles de instalación o características del activo..."></textarea>
            </div>
        `
    }
};

// ==========================================================
// INICIALIZACIÓN
// ==========================================================
document.addEventListener('DOMContentLoaded', () => {
    // Listeners para tabs del modal de detalles
    const tabs = document.querySelectorAll('.v-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.v-tab-pane').forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            const target = tab.getAttribute('data-vtab');
            const targetEl = document.getElementById(`vtab-${target}`);
            if (targetEl) targetEl.classList.add('active');
        });
    });

    // Listeners para búsqueda y filtros
    const searchInput = document.getElementById('searchVehicles');
    const filterCategory = document.getElementById('filterCategory');
    const filterStatus = document.getElementById('filterStatus');

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const btnClear = document.getElementById('btnClearSearch');
            if (btnClear) {
                btnClear.style.display = searchInput.value.trim().length > 0 ? 'flex' : 'none';
            }
            renderFilteredVehicles();
        });
    }

    if (filterCategory) {
        filterCategory.addEventListener('change', () => {
            const filterStatusEl = document.getElementById('filterStatus');
            if (filterStatusEl) filterStatusEl.value = '';
            syncStatPillActive(filterCategory.value, '');
            renderFilteredVehicles();
        });
    }

    if (filterStatus) {
        filterStatus.addEventListener('change', () => {
            syncStatPillActive('', filterStatus.value);
            renderFilteredVehicles();
        });
    }

    // Inicializar contenedor dinámico del modal con categoría por defecto
    renderDynamicAssetFields('vehiculo');

    // Cargar Catálogo de Activos
    loadVehicles();
});

// ==========================================================
// SELECCIÓN Y RENDERIZADO DINÁMICO DE CATEGORÍAS
// ==========================================================
function selectAssetCategory(category, element) {
    currentSelectedCategory = category;
    const catInput = document.getElementById('nvCategoria');
    if (catInput) catInput.value = category;

    // Actualizar clase activa en tarjetas selectoras
    document.querySelectorAll('.cat-card-opt').forEach(el => el.classList.remove('active'));
    if (element) {
        element.classList.add('active');
    } else {
        const target = document.querySelector(`.cat-card-opt[data-cat="${category}"]`);
        if (target) target.classList.add('active');
    }

    renderDynamicAssetFields(category);
}

function renderDynamicAssetFields(category, data = {}) {
    const container = document.getElementById('dynamicAssetFieldsContainer');
    if (!container) return;

    const catConfig = ASSET_CATEGORIES[category] || ASSET_CATEGORIES.otro;
    container.innerHTML = catConfig.renderFields(data);

    // Auto-formateo en mayúsculas para placa o código
    const nvPlaca = document.getElementById('nvPlaca');
    if (nvPlaca) {
        nvPlaca.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
}

function selectNewVehicleSubtype(type, el) {
    const inputTipo = document.getElementById('nvTipo');
    if (inputTipo) inputTipo.value = type;

    document.querySelectorAll('.type-card-opt').forEach(opt => opt.classList.remove('active'));
    if (el) el.classList.add('active');
}

// Limpiar campo de búsqueda
function clearSearchInput() {
    const searchInput = document.getElementById('searchVehicles');
    if (searchInput) {
        searchInput.value = '';
        const btn = document.getElementById('btnClearSearch');
        if (btn) btn.style.display = 'none';
        renderFilteredVehicles();
    }
}

// Sincronizar pill activo con el filtro actual
function syncStatPillActive(category, status) {
    document.querySelectorAll('.stat-pill').forEach(p => p.classList.remove('active'));
    if (status === 'mantenimiento') {
        const p = document.querySelector('.stat-pill:last-child');
        if (p) p.classList.add('active');
    } else if (!category) {
        const p = document.querySelector('.stat-pill.stat-all');
        if (p) p.classList.add('active');
    } else {
        const p = document.querySelector(`.stat-pill[onclick*="'${category}'"]`);
        if (p) p.classList.add('active');
    }
}

// Filtro rápido por categoría desde los pills superiores
function filterByCategoryQuick(category, element) {
    document.querySelectorAll('.stat-pill').forEach(p => p.classList.remove('active'));
    if (element) {
        element.classList.add('active');
    } else {
        syncStatPillActive(category, '');
    }
    const filterCatEl = document.getElementById('filterCategory');
    if (filterCatEl) filterCatEl.value = category;
    const filterStatusEl = document.getElementById('filterStatus');
    if (filterStatusEl) filterStatusEl.value = '';
    renderFilteredVehicles();
}

function filterByStatusQuick(status, element) {
    document.querySelectorAll('.stat-pill').forEach(p => p.classList.remove('active'));
    if (element) {
        element.classList.add('active');
    } else {
        syncStatPillActive('', status);
    }
    const filterStatusEl = document.getElementById('filterStatus');
    if (filterStatusEl) filterStatusEl.value = status;
    const filterCatEl = document.getElementById('filterCategory');
    if (filterCatEl) filterCatEl.value = '';
    renderFilteredVehicles();
}

function resetFilters() {
    const s = document.getElementById('searchVehicles');
    const c = document.getElementById('filterCategory');
    const st = document.getElementById('filterStatus');
    if (s) s.value = '';
    if (c) c.value = '';
    if (st) st.value = '';
    syncStatPillActive('', '');
    renderFilteredVehicles();
}

// ==========================================================
// CARGAR ACTIVOS DESDE EL SERVIDOR (SIN DUPLICADOS)
// ==========================================================
async function loadVehicles() {
    const refreshIcon = document.getElementById('refreshIcon');
    if (refreshIcon) refreshIcon.classList.add('ph-spin');

    const container = document.getElementById('vehiculosContainer');
    if (container && allVehicles.length === 0) {
        container.innerHTML = `
            <div class="grid-loading-placeholder">
                <i class="ph-bold ph-spinner ph-spin"></i>
                <p>Cargando catálogo de activos empresariales...</p>
            </div>
        `;
    }

    try {
        const res = await fetch(`${baseUrl}/ajax/activos.php?action=get_activos&_t=${Date.now()}`);
        const data = await res.json();
        
        if (data.success) {
            // Deduplicar activos por ID de forma segura
            const rawList = data.data || [];
            const map = new Map();
            rawList.forEach(item => {
                if (item && item.id) map.set(item.id, item);
            });
            allVehicles = Array.from(map.values());

            updateKpiStats(allVehicles);
            renderFilteredVehicles();
        } else {
            if (container) {
                container.innerHTML = `
                    <div class="grid-loading-placeholder" style="color:#ef4444;">
                        <i class="ph-bold ph-warning-circle"></i>
                        <p>Error: ${data.error || 'No se pudieron cargar los activos'}</p>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Error cargando activos:', error);
        if (container) {
            container.innerHTML = `
                <div class="grid-loading-placeholder" style="color:#ef4444;">
                    <i class="ph-bold ph-warning-circle"></i>
                    <p>Error de conexión al cargar activos</p>
                </div>
            `;
        }
    } finally {
        if (refreshIcon) {
            setTimeout(() => refreshIcon.classList.remove('ph-spin'), 400);
        }
    }
}

// ==========================================================
// ACTUALIZAR CONTADORES KPI
// ==========================================================
function updateKpiStats(activos) {
    const total = activos.length;
    const vehiculos = activos.filter(v => (v.categoria === 'vehiculo' || ['auto','camion','motocicleta'].includes((v.tipo || '').toLowerCase()))).length;
    const tecnologia = activos.filter(v => (v.categoria === 'tecnologia' || ['monitor','laptop','desktop','pc','servidor','impresora','switch','router','tablet','otro_tech'].includes((v.tipo || '').toLowerCase()))).length;
    const mobiliario = activos.filter(v => (v.categoria === 'mobiliario' || ['escritorio','silla','archivador','estante','mueble','mesa','otro_mueble'].includes((v.tipo || '').toLowerCase()))).length;
    const herramientas = activos.filter(v => (v.categoria === 'herramientas' || ['fusionadora','otdr','escalera','taladro','herramienta','generador','kit_herramientas','otra_herramienta'].includes((v.tipo || '').toLowerCase()))).length;
    const mantenimiento = activos.filter(v => ['mantenimiento', 'taller', 'reparacion'].includes((v.estado || '').toLowerCase())).length;

    const elTotal = document.getElementById('kpiTotalActivos');
    const elVeh = document.getElementById('kpiTotalVehiculos');
    const elTec = document.getElementById('kpiTotalTecnologia');
    const elMob = document.getElementById('kpiTotalMobiliario');
    const elHer = document.getElementById('kpiTotalHerramientas');
    const elMant = document.getElementById('kpiTotalMantenimiento');

    if (elTotal) elTotal.innerText = total;
    if (elVeh) elVeh.innerText = vehiculos;
    if (elTec) elTec.innerText = tecnologia;
    if (elMob) elMob.innerText = mobiliario;
    if (elHer) elHer.innerText = herramientas;
    if (elMant) elMant.innerText = mantenimiento;
}

// ==========================================================
// RENDERIZADO FILTRADO DE TARJETAS (LIMPIEZA TOTAL PREVIA)
// ==========================================================
function renderFilteredVehicles() {
    const container = document.getElementById('vehiculosContainer');
    if (!container) return;

    // LIMPIEZA ABSOLUTA DEL CONTENEDOR PARA EVITAR CUALQUIER DUPLICACIÓN
    container.innerHTML = '';

    const query = (document.getElementById('searchVehicles')?.value || '').toLowerCase().trim();
    const catFilter = (document.getElementById('filterCategory')?.value || '').toLowerCase().trim();
    const statusFilter = (document.getElementById('filterStatus')?.value || '').toLowerCase().trim();

    const filtered = allVehicles.filter(v => {
        const placa = (v.placa || v.codigo_identificador || '').toLowerCase();
        const nombre = (v.nombre || '').toLowerCase();
        const marca = (v.marca || '').toLowerCase();
        const modelo = (v.modelo || '').toLowerCase();
        const ubicacion = (v.ubicacion || '').toLowerCase();
        const responsable = (v.responsable_nombre || '').toLowerCase();
        const tipo = (v.tipo || '').toLowerCase();
        const cat = (v.categoria || '').toLowerCase();
        const estado = (v.estado || '').toLowerCase();

        // Determinar categoría normalizada
        let normalCat = cat;
        if (!normalCat || normalCat === 'vehiculo') {
            if (['auto','camion','motocicleta'].includes(tipo)) normalCat = 'vehiculo';
            else if (['monitor','laptop','desktop','pc','servidor','impresora','switch','router','tablet','otro_tech'].includes(tipo)) normalCat = 'tecnologia';
            else if (['escritorio','silla','archivador','estante','mueble','mesa','otro_mueble'].includes(tipo)) normalCat = 'mobiliario';
            else if (['fusionadora','otdr','escalera','taladro','herramienta','generador','kit_herramientas','otra_herramienta'].includes(tipo)) normalCat = 'herramientas';
            else if (cat) normalCat = cat;
            else normalCat = 'vehiculo';
        }

        const matchesQuery = !query || 
            placa.includes(query) || 
            nombre.includes(query) || 
            marca.includes(query) || 
            modelo.includes(query) || 
            ubicacion.includes(query) || 
            responsable.includes(query) || 
            tipo.includes(query);

        const matchesCat = !catFilter || normalCat === catFilter || tipo === catFilter;
        const matchesStatus = !statusFilter || 
            (statusFilter === 'mantenimiento' ? ['mantenimiento', 'taller', 'reparacion'].includes(estado) : estado === statusFilter);

        return matchesQuery && matchesCat && matchesStatus;
    });

    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="grid-loading-placeholder">
                <i class="ph-bold ph-magnifying-glass" style="color:var(--text-muted); opacity:0.5;"></i>
                <p>No se encontraron activos con los filtros aplicados.</p>
                <button type="button" class="btn btn-sm btn-action-pill mt-2" onclick="resetFilters()">Limpiar Filtros</button>
            </div>
        `;
        return;
    }

    filtered.forEach(v => {
        const estado = (v.estado || 'activo').toLowerCase();
        let badgeClass = 'badge-activo';
        let estadoLabel = 'Activo';
        if (estado === 'mantenimiento') { badgeClass = 'badge-mantenimiento'; estadoLabel = 'Mantenimiento'; }
        else if (estado === 'taller' || estado === 'reparacion') { badgeClass = 'badge-taller'; estadoLabel = 'En Reparación'; }
        else if (estado === 'inactivo' || estado === 'baja') { badgeClass = 'badge-inactivo'; estadoLabel = 'Inactivo / Baja'; }

        const tipo = (v.tipo || 'otro').toLowerCase();
        const categoria = (v.categoria || 'vehiculo').toLowerCase();

        // Icono y etiquetas según tipo y categoría
        let typeIcon = 'ph-cube';
        let catLabel = 'Activo';
        let catBadgeClass = 'badge-cat-otro';

        if (categoria === 'vehiculo' || ['auto', 'camion', 'motocicleta'].includes(tipo)) {
            catLabel = 'Vehículo';
            catBadgeClass = 'badge-cat-vehiculo';
            typeIcon = tipo === 'camion' ? 'ph-truck' : (tipo === 'motocicleta' ? 'ph-motorcycle' : 'ph-car');
        } else if (categoria === 'tecnologia' || ['monitor', 'laptop', 'desktop', 'pc', 'servidor', 'impresora', 'switch'].includes(tipo)) {
            catLabel = 'Tecnología';
            catBadgeClass = 'badge-cat-tecnologia';
            typeIcon = tipo === 'laptop' ? 'ph-laptop' : (tipo === 'impresora' ? 'ph-printer' : (tipo === 'switch' ? 'ph-git-fork' : 'ph-desktop'));
        } else if (categoria === 'mobiliario' || ['escritorio', 'silla', 'archivador', 'estante', 'mueble', 'mesa'].includes(tipo)) {
            catLabel = 'Mobiliario';
            catBadgeClass = 'badge-cat-mobiliario';
            typeIcon = tipo === 'silla' ? 'ph-armchair' : (tipo === 'archivador' ? 'ph-archive' : 'ph-table');
        } else if (categoria === 'herramientas' || ['fusionadora', 'otdr', 'escalera', 'taladro', 'herramienta'].includes(tipo)) {
            catLabel = 'Herramienta';
            catBadgeClass = 'badge-cat-herramientas';
            typeIcon = tipo === 'fusionadora' ? 'ph-lightning' : (tipo === 'escalera' ? 'ph-ladder' : 'ph-hammer');
        }

        const codigoOrPlaca = v.codigo_identificador || v.placa || 'S/C';
        const displayName = v.nombre || `${v.marca || ''} ${v.modelo || ''}`.trim() || `Activo #${v.id}`;
        const brandModel = `${v.marca || ''} ${v.modelo || ''}`.trim();

        // Renderizado del Header de Imagen
        let imgHeaderHtml = '';
        if (v.primera_foto) {
            imgHeaderHtml = `
                <img src="${baseUrl}/uploads/activos/${v.primera_foto}" class="card-vehicle-img" alt="${displayName}" loading="lazy" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\\'card-img-placeholder\\'><i class=\\'ph-bold ${typeIcon}\\'></i><span>${catLabel}</span></div>';">
            `;
        } else {
            imgHeaderHtml = `
                <div class="card-img-placeholder">
                    <i class="ph-bold ${typeIcon}"></i>
                    <span>${catLabel}</span>
                </div>
            `;
        }

        const card = document.createElement('div');
        card.className = 'vehiculo-card-modern';
        card.onclick = () => openAssetDetails(v.id);

        card.innerHTML = `
            <div class="card-img-header">
                ${imgHeaderHtml}
                <div class="card-type-tag">
                    <i class="ph-bold ${typeIcon}"></i> ${catLabel}
                </div>
                <div class="card-top-right-group">
                    <span class="card-status-badge status-badge ${badgeClass}">${estadoLabel}</span>
                    <div class="card-quick-actions">
                        <button type="button" class="btn-card-action btn-card-edit" onclick="editAssetDirect(${v.id}, event)" title="Editar Activo">
                            <i class="ph-bold ph-pencil-simple"></i>
                        </button>
                        <button type="button" class="btn-card-action btn-card-delete" onclick="deleteAssetDirect(${v.id}, '${esc(displayName).replace(/'/g, "\\'")}', event)" title="Eliminar Activo">
                            <i class="ph-bold ph-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-vehicle-body">
                <div>
                    <div class="plate-badge-row">
                        <span class="plate-pill">${codigoOrPlaca}</span>
                        ${v.ubicacion ? `<span class="badge ${catBadgeClass}" style="font-size:0.72rem;"><i class="ph-bold ph-map-pin"></i> ${v.ubicacion}</span>` : ''}
                    </div>
                    <h3 class="vehicle-brand-title" title="${displayName}">${displayName}</h3>
                    ${brandModel && brandModel !== displayName ? `<p class="text-muted small mb-2"><i class="ph ph-tag"></i> ${brandModel}</p>` : ''}
                    ${v.responsable_nombre ? `
                        <div class="asset-responsible-tag">
                            <i class="ph-bold ph-user"></i>
                            <span>${v.responsable_nombre}</span>
                        </div>
                    ` : ''}
                </div>
                <div class="vehicle-card-meta">
                    <div class="d-flex align-items-center gap-3">
                        <span class="meta-item" title="Documentos adjuntos"><i class="ph-bold ph-files"></i> ${v.total_docs || 0}</span>
                        <span class="meta-item" title="Fotos en galería"><i class="ph-bold ph-images"></i> ${v.total_imgs || 0}</span>
                        <span class="meta-item" title="Historial"><i class="ph-bold ph-wrench"></i> ${v.total_historial || 0}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn-card-inline-action btn-inline-edit" onclick="editAssetDirect(${v.id}, event)" title="Editar activo">
                            <i class="ph-bold ph-pencil-simple"></i>
                        </button>
                        <button type="button" class="btn-card-inline-action btn-inline-del" onclick="deleteAssetDirect(${v.id}, '${esc(displayName).replace(/'/g, "\\'")}', event)" title="Eliminar activo">
                            <i class="ph-bold ph-trash"></i>
                        </button>
                        <span class="meta-item text-primary font-weight-bold" style="cursor:pointer;">
                            Ver Ficha <i class="ph-bold ph-caret-right"></i>
                        </span>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(card);
    });
}

// ── Acciones Directas de Edición y Eliminación desde Cards ─────
window.editAssetDirect = function(assetId, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    currentVehiculoId = assetId;
    openEditVehiculo();
};

window.deleteAssetDirect = async function(assetId, assetName, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    currentVehiculoId = assetId;
    if (!confirm(`¿Estás seguro de eliminar el activo "${assetName || 'este activo'}"?`)) return;
    try {
        const formData = new FormData();
        formData.append('action', 'delete_vehiculo');
        formData.append('id', assetId);
        appendCsrf(formData);

        const res = await fetch(`${baseUrl}/ajax/activos.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            if (window.showToast) {
                window.showToast('Activo eliminado correctamente', 'success');
            } else {
                alert('Activo eliminado correctamente');
            }
            await loadVehicles();
        } else {
            alert('Error al eliminar activo: ' + (data.error || 'Error'));
        }
    } catch (err) {
        console.error(err);
        alert('Error en el servidor al eliminar.');
    }
};

// ==========================================================
// MODAL: REGISTRAR NUEVO ACTIVO
// ==========================================================
function openNewAssetModal() {
    const form = document.getElementById('formNuevoVehiculo');
    if (form) form.reset();
    const previews = document.getElementById('nvPhotoPreviews');
    if (previews) previews.innerHTML = '';
    selectAssetCategory('vehiculo');
    openModal('modalNuevoVehiculo');
}
function openNewVehicleModal() { openNewAssetModal(); }

// Previsualización de imágenes seleccionadas en Dropzone
function handleNvPhotoPreviews(input) {
    const previewGrid = document.getElementById('nvPhotoPreviews');
    if (!previewGrid) return;
    previewGrid.innerHTML = '';

    if (input.files && input.files.length > 0) {
        Array.from(input.files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const item = document.createElement('div');
                    item.className = 'photo-preview-item';
                    item.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="btn-remove-preview" onclick="removePhotoFromInput(${index})">&times;</button>
                    `;
                    previewGrid.appendChild(item);
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

function removePhotoFromInput(index) {
    const input = document.getElementById('nvFotos');
    if (!input || !input.files) return;
    const dt = new DataTransfer();
    Array.from(input.files).forEach((file, i) => {
        if (i !== index) dt.items.add(file);
    });
    input.files = dt.files;
    handleNvPhotoPreviews(input);
}

// Enviar formulario de nuevo activo con prevención estricta de doble clic
const formNuevoVehiculo = document.getElementById('formNuevoVehiculo');
if (formNuevoVehiculo) {
    formNuevoVehiculo.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (isSavingAsset) return;
        isSavingAsset = true;

        const btnSave = document.getElementById('btnSaveNewVehicle');
        if (btnSave) {
            btnSave.disabled = true;
            btnSave.innerHTML = `<i class="ph-bold ph-spinner ph-spin"></i> Guardando...`;
        }

        try {
            const formData = new FormData();
            const categoria = document.getElementById('nvCategoria')?.value || 'vehiculo';
            const tipo = document.getElementById('nvTipo')?.value || 'otro';
            const placa = document.getElementById('nvPlaca')?.value.trim() || '';
            const nombre = document.getElementById('nvNombre')?.value.trim() || '';
            const marca = document.getElementById('nvMarca')?.value.trim() || '';
            const modelo = document.getElementById('nvModelo')?.value.trim() || '';
            const ubicacion = document.getElementById('nvUbicacion')?.value.trim() || '';
            const responsable = document.getElementById('nvResponsable')?.value.trim() || '';
            const estado = document.getElementById('nvEstado')?.value || 'activo';
            const valor = document.getElementById('nvValor')?.value || 0;
            const fechaAdquisicion = document.getElementById('nvFechaAdquisicion')?.value || '';

            // Recolectar detalles extra según categoría
            const extraDetails = {};
            if (categoria === 'vehiculo') {
                extraDetails.anio = document.getElementById('nvExtraAnio')?.value || '';
                extraDetails.combustible = document.getElementById('nvExtraCombustible')?.value || '';
                extraDetails.kilometraje = document.getElementById('nvExtraKm')?.value || '';
            } else if (categoria === 'tecnologia') {
                extraDetails.pantalla = document.getElementById('nvExtraTech1')?.value || '';
                extraDetails.procesador = document.getElementById('nvExtraTech2')?.value || '';
                extraDetails.memoria = document.getElementById('nvExtraTech3')?.value || '';
            } else if (categoria === 'mobiliario') {
                extraDetails.material = document.getElementById('nvExtraMobMaterial')?.value || '';
                extraDetails.dimensiones = document.getElementById('nvExtraMobDimensiones')?.value || '';
                extraDetails.color = document.getElementById('nvExtraMobColor')?.value || '';
                extraDetails.condicion = document.getElementById('nvExtraMobCondicion')?.value || '';
            } else if (categoria === 'herramientas') {
                extraDetails.calibracion = document.getElementById('nvExtraHerrCalibracion')?.value || '';
                extraDetails.cuadrilla = document.getElementById('nvExtraHerrCuadrilla')?.value || '';
            } else if (categoria === 'otro') {
                extraDetails.observaciones = document.getElementById('nvExtraOtroNotas')?.value || '';
            }

            formData.append('action', 'save_activo');
            formData.append('categoria', categoria);
            formData.append('tipo', tipo);
            formData.append('placa', placa);
            formData.append('codigo_identificador', placa);
            formData.append('nombre', nombre);
            formData.append('marca', marca);
            formData.append('modelo', modelo);
            formData.append('ubicacion', ubicacion);
            formData.append('responsable_nombre', responsable);
            formData.append('estado', estado);
            formData.append('valor_adquisicion', valor);
            formData.append('fecha_adquisicion', fechaAdquisicion);
            formData.append('detalles_extra', JSON.stringify(extraDetails));

            // Adjuntar fotos
            const inputFotos = document.getElementById('nvFotos');
            if (inputFotos && inputFotos.files) {
                for (let i = 0; i < inputFotos.files.length; i++) {
                    formData.append('fotos_vehiculo[]', inputFotos.files[i]);
                }
            }

            const res = await fetch(`${baseUrl}/ajax/activos.php`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                closeModal('modalNuevoVehiculo');
                formNuevoVehiculo.reset();
                const previews = document.getElementById('nvPhotoPreviews');
                if (previews) previews.innerHTML = '';
                selectAssetCategory('vehiculo');
                await loadVehicles();
            } else {
                alert('Error al guardar activo: ' + (data.error || 'Error desconocido'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Ocurrió un error en el servidor al intentar registrar el activo.');
        } finally {
            isSavingAsset = false;
            if (btnSave) {
                btnSave.disabled = false;
                btnSave.innerHTML = `<i class="ph-bold ph-check"></i> Guardar Activo`;
            }
        }
    });
}

// ==========================================================
// MODAL: DETALLE DEL ACTIVO
// ==========================================================
async function openAssetDetails(id) {
    currentVehiculoId = id;

    // Reset tabs
    document.querySelectorAll('.v-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.v-tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelector('.v-tab[data-vtab="info"]')?.classList.add('active');
    document.getElementById('vtab-info')?.classList.add('active');

    try {
        const res = await fetch(`${baseUrl}/ajax/activos.php?action=get_vehiculo_detalle&id=${id}&_t=${Date.now()}`);
        const data = await res.json();

        if (data.success && data.data) {
            const v = data.data;

            const categoria = (v.categoria || 'vehiculo').toLowerCase();
            const tipo = (v.tipo || 'otro').toLowerCase();
            const isVehicle = categoria === 'vehiculo' || ['auto', 'camion', 'motocicleta'].includes(tipo);

            // Icono cabecera
            let badgeIcon = 'ph-cube';
            if (isVehicle) badgeIcon = tipo === 'camion' ? 'ph-truck' : (tipo === 'motocicleta' ? 'ph-motorcycle' : 'ph-car');
            else if (categoria === 'tecnologia') badgeIcon = 'ph-desktop';
            else if (categoria === 'mobiliario') badgeIcon = 'ph-armchair';
            else if (categoria === 'herramientas') badgeIcon = 'ph-hammer';

            const iconBadgeEl = document.getElementById('modalDetailIconBadge');
            if (iconBadgeEl) iconBadgeEl.innerHTML = `<i class="ph-bold ${badgeIcon}"></i>`;

            // Título y Subtítulo
            const codigo = v.codigo_identificador || v.placa || 'S/C';
            const nombre = v.nombre || `${v.marca || ''} ${v.modelo || ''}`.trim() || `Activo #${v.id}`;
            document.getElementById('modalTitlePlaca').innerText = codigo;
            document.getElementById('modalSubtitleVehicle').innerText = `${nombre} · ${v.ubicacion || 'Sin Ubicación'}`;

            // Sincronizar Estado Badges (Cabecera y Tab 1)
            const badges = [document.getElementById('lblEstadoBadge'), document.getElementById('lblEstadoBadgeTab')].filter(Boolean);
            badges.forEach(badge => {
                const est = (v.estado || 'activo').toLowerCase();
                badge.className = 'status-badge';
                if (est === 'activo') { badge.classList.add('badge-activo'); badge.innerText = 'Activo'; }
                else if (est === 'mantenimiento') { badge.classList.add('badge-mantenimiento'); badge.innerText = 'En Mantenimiento'; }
                else if (est === 'taller' || est === 'reparacion') { badge.classList.add('badge-taller'); badge.innerText = 'En Reparación'; }
                else { badge.classList.add('badge-inactivo'); badge.innerText = 'Inactivo / Baja'; }
            });

            // Mostrar u ocultar pestaña de llantas (solo vehículos)
            const tabLlantasBtn = document.getElementById('tabLlantasBtn');
            if (tabLlantasBtn) {
                tabLlantasBtn.style.display = isVehicle ? 'inline-flex' : 'none';
            }

            // Renderizar Especificaciones Dinámicas
            renderDetailDynamicSpecs(v);

            // Renderizar Documentos
            renderDocumentList(v.documentos || []);

            // Renderizar Galería Fotográfica
            renderGalleryList(v.imagenes || []);

            // Renderizar Historial
            renderHistoryTables(v.historial || []);

            openModal('modalDetalleVehiculo');
        } else {
            alert('Error cargando detalles: ' + (data.error || 'No encontrado'));
        }
    } catch (e) {
        console.error(e);
        alert('Error de conexión al cargar ficha del activo.');
    }
}
function openVehicleDetails(id) { openAssetDetails(id); }

// Renderizar tabla de especificaciones dinámicas
function renderDetailDynamicSpecs(v) {
    const container = document.getElementById('dynamicSpecsList');
    if (!container) return;

    let extra = {};
    try {
        if (v.detalles_extra) {
            extra = typeof v.detalles_extra === 'string' ? JSON.parse(v.detalles_extra) : v.detalles_extra;
        }
    } catch (e) {}

    let rows = `
        <div class="spec-row">
            <span class="spec-name"><i class="ph ph-barcode"></i> Código / Placa</span>
            <strong class="spec-val font-monospace plate-tag">${v.codigo_identificador || v.placa || '--'}</strong>
        </div>
        <div class="spec-row">
            <span class="spec-name"><i class="ph ph-cube"></i> Nombre / Título</span>
            <strong class="spec-val">${v.nombre || '--'}</strong>
        </div>
        <div class="spec-row">
            <span class="spec-name"><i class="ph ph-tag"></i> Tipo</span>
            <strong class="spec-val text-capitalize">${v.tipo || '--'}</strong>
        </div>
        <div class="spec-row">
            <span class="spec-name"><i class="ph ph-buildings"></i> Marca</span>
            <strong class="spec-val">${v.marca || '--'}</strong>
        </div>
        <div class="spec-row">
            <span class="spec-name"><i class="ph ph-steering-wheel"></i> Modelo</span>
            <strong class="spec-val">${v.modelo || '--'}</strong>
        </div>
        <div class="spec-row">
            <span class="spec-name"><i class="ph ph-map-pin"></i> Ubicación / Área</span>
            <strong class="spec-val">${v.ubicacion || 'No asignada'}</strong>
        </div>
        <div class="spec-row">
            <span class="spec-name"><i class="ph ph-user"></i> Responsable</span>
            <strong class="spec-val">${v.responsable_nombre || 'No asignado'}</strong>
        </div>
        <div class="spec-row">
            <span class="spec-name"><i class="ph ph-currency-dollar"></i> Valor Adquisición</span>
            <strong class="spec-val text-success">S/. ${parseFloat(v.valor_adquisicion || 0).toFixed(2)}</strong>
        </div>
    `;

    // Añadir especificaciones extra si existen
    if (extra.pantalla) rows += `<div class="spec-row"><span class="spec-name"><i class="ph ph-monitor"></i> Pantalla</span><strong class="spec-val">${extra.pantalla}</strong></div>`;
    if (extra.procesador) rows += `<div class="spec-row"><span class="spec-name"><i class="ph ph-cpu"></i> Procesador</span><strong class="spec-val">${extra.procesador}</strong></div>`;
    if (extra.memoria) rows += `<div class="spec-row"><span class="spec-name"><i class="ph ph-hard-drive"></i> RAM / Disco</span><strong class="spec-val">${extra.memoria}</strong></div>`;
    if (extra.material) rows += `<div class="spec-row"><span class="spec-name"><i class="ph ph-hammer"></i> Material</span><strong class="spec-val">${extra.material}</strong></div>`;
    if (extra.dimensiones) rows += `<div class="spec-row"><span class="spec-name"><i class="ph ph-ruler"></i> Dimensiones</span><strong class="spec-val">${extra.dimensiones}</strong></div>`;
    if (extra.color) rows += `<div class="spec-row"><span class="spec-name"><i class="ph ph-palette"></i> Color / Acabado</span><strong class="spec-val">${extra.color}</strong></div>`;
    if (extra.condicion) rows += `<div class="spec-row"><span class="spec-name"><i class="ph ph-sparkle"></i> Condición</span><strong class="spec-val">${extra.condicion}</strong></div>`;
    if (extra.kilometraje) rows += `<div class="spec-row"><span class="spec-name"><i class="ph ph-gauge"></i> Kilometraje</span><strong class="spec-val">${extra.kilometraje}</strong></div>`;
    if (extra.combustible) rows += `<div class="spec-row"><span class="spec-name"><i class="ph ph-gas-pump"></i> Combustible</span><strong class="spec-val">${extra.combustible}</strong></div>`;
    if (extra.calibracion) rows += `<div class="spec-row"><span class="spec-name"><i class="ph ph-calendar"></i> Próx. Calibración</span><strong class="spec-val text-warning">${extra.calibracion}</strong></div>`;
    if (extra.observaciones) rows += `<div class="spec-row"><span class="spec-name"><i class="ph ph-note"></i> Notas</span><strong class="spec-val">${extra.observaciones}</strong></div>`;

    container.innerHTML = rows;
}

// ==========================================================
// RENDERIZADO DE DOCUMENTOS
// ==========================================================
function renderDocumentList(docs) {
    const list = document.getElementById('docsList');
    if (!list) return;
    list.innerHTML = '';

    if (docs.length === 0) {
        list.innerHTML = `
            <li class="empty-docs-placeholder">
                <i class="ph-bold ph-folder-open"></i>
                <p>No hay documentos registrados para este activo.</p>
            </li>
        `;
        return;
    }

    docs.forEach(d => {
        const li = document.createElement('li');
        li.className = 'v-doc-item';
        li.innerHTML = `
            <div class="doc-icon"><i class="ph-bold ph-file-text"></i></div>
            <div class="doc-meta-info">
                <span class="doc-title">${d.titulo}</span>
                <span class="doc-sub">${d.tipo_documento.replace('_', ' ').toUpperCase()} &middot; Subido el ${d.fecha_subida ? d.fecha_subida.substring(0, 10) : ''}</span>
            </div>
            <div class="doc-actions">
                <a href="${baseUrl}/uploads/activos/${d.url_archivo}" target="_blank" class="btn-doc-view" title="Ver archivo">
                    <i class="ph-bold ph-eye"></i>
                </a>
                <button type="button" class="btn-doc-edit" onclick="openEditDocModal(${d.id}, '${d.tipo_documento}', '${escapeQuotes(d.titulo)}')" title="Editar">
                    <i class="ph-bold ph-pencil"></i>
                </button>
                <button type="button" class="btn-doc-delete" onclick="deleteDoc(${d.id})" title="Eliminar">
                    <i class="ph-bold ph-trash"></i>
                </button>
            </div>
        `;
        list.appendChild(li);
    });
}

// ==========================================================
// RENDERIZADO DE GALERÍA & LIGHTBOX
// ==========================================================
function renderGalleryList(imgs) {
    const container = document.getElementById('galleryList');
    if (!container) return;
    container.innerHTML = '';

    if (imgs.length === 0) {
        container.innerHTML = `
            <div class="empty-gallery-placeholder">
                <i class="ph-bold ph-camera"></i>
                <p>Aún no hay fotos registradas en la galería.</p>
            </div>
        `;
        return;
    }

    imgs.forEach(img => {
        const item = document.createElement('div');
        item.className = 'gallery-photo-card';
        item.onclick = () => openImageViewer(`${baseUrl}/uploads/activos/${img.url_imagen}`);
        item.innerHTML = `
            <img src="${baseUrl}/uploads/activos/${img.url_imagen}" alt="${img.descripcion || 'Foto activo'}" loading="lazy">
            <div class="gallery-photo-overlay">
                <p>${img.descripcion || 'Foto de estado'}</p>
                <small><i class="ph-bold ph-calendar"></i> ${img.fecha_subida || ''}</small>
            </div>
            <div class="gallery-zoom-badge"><i class="ph-bold ph-magnifying-glass-plus"></i></div>
        `;
        container.appendChild(item);
    });
}

function openImageViewer(url) {
    const overlay = document.getElementById('imageViewerOverlay');
    const target = document.getElementById('viewerImageTarget');
    if (overlay && target) {
        target.src = url;
        overlay.classList.add('active');
        overlay.classList.add('show');
        overlay.style.display = 'flex';
    }
}

function closeImageViewer() {
    const overlay = document.getElementById('imageViewerOverlay');
    if (overlay) {
        overlay.classList.remove('active');
        overlay.classList.remove('show');
        overlay.style.display = 'none';
    }
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeImageViewer();
});

// ==========================================================
// RENDERIZADO DE HISTORIAL (MANTENIMIENTOS & LLANTAS)
// ==========================================================
function renderHistoryTables(history) {
    const tblMant = document.getElementById('mantenimientoHistoryTable');
    const tblLlantas = document.getElementById('llantasHistoryTable');

    if (tblMant) tblMant.innerHTML = '';
    if (tblLlantas) tblLlantas.innerHTML = '';

    const mantenimientos = history.filter(h => h.tipo_evento !== 'cambio_llantas');
    const llantas = history.filter(h => h.tipo_evento === 'cambio_llantas');

    if (tblMant) {
        if (mantenimientos.length === 0) {
            tblMant.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">No hay servicios o mantenimientos registrados.</td></tr>`;
        } else {
            mantenimientos.forEach(h => {
                let fotosBtn = '<span class="text-muted">Sin fotos</span>';
                if (h.fotos_adjuntas) {
                    try {
                        const fotos = JSON.parse(h.fotos_adjuntas);
                        if (fotos && fotos.length > 0) {
                            fotosBtn = `<button type="button" class="btn btn-sm btn-action-pill" onclick="viewHistoryPhotos('${encodeURIComponent(h.fotos_adjuntas)}')"><i class="ph-bold ph-images"></i> ${fotos.length} foto(s)</button>`;
                        }
                    } catch (e) {}
                }

                tblMant.innerHTML += `
                    <tr>
                        <td><strong>${h.fecha_evento}</strong></td>
                        <td><span class="badge badge-cat-otro text-capitalize">${h.tipo_evento.replace('_', ' ')}</span></td>
                        <td>${h.descripcion}</td>
                        <td class="text-success font-weight-bold">S/. ${parseFloat(h.costo || 0).toFixed(2)}</td>
                        <td>${fotosBtn}</td>
                    </tr>
                `;
            });
        }
    }

    if (tblLlantas) {
        if (llantas.length === 0) {
            tblLlantas.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">No hay cambios de llantas registrados.</td></tr>`;
        } else {
            llantas.forEach(h => {
                tblLlantas.innerHTML += `
                    <tr>
                        <td><strong>${h.fecha_evento}</strong></td>
                        <td>${h.descripcion}</td>
                        <td class="text-success font-weight-bold">S/. ${parseFloat(h.costo || 0).toFixed(2)}</td>
                        <td>${h.registrador || 'Admin'}</td>
                    </tr>
                `;
            });
        }
    }
}

function viewHistoryPhotos(jsonEncoded) {
    try {
        const fotos = JSON.parse(decodeURIComponent(jsonEncoded));
        if (fotos && fotos.length > 0) {
            openImageViewer(`${baseUrl}/uploads/activos/${fotos[0]}`);
        }
    } catch (e) {}
}

// ==========================================================
// MODAL: EDITAR ACTIVO
// ==========================================================
function openEditVehiculo() {
    const v = allVehicles.find(item => item.id == currentVehiculoId);
    if (!v) return;

    document.getElementById('evnCategoria').value = v.categoria || 'vehiculo';
    document.getElementById('evnNombre').value = v.nombre || '';
    document.getElementById('evnPlaca').value = v.codigo_identificador || v.placa || '';
    document.getElementById('evnTipo').value = v.tipo || '';
    document.getElementById('evnMarca').value = v.marca || '';
    document.getElementById('evnModelo').value = v.modelo || '';
    document.getElementById('evnUbicacion').value = v.ubicacion || '';
    document.getElementById('evnResponsable').value = v.responsable_nombre || '';
    document.getElementById('evnEstado').value = v.estado || 'activo';
    document.getElementById('evnValor').value = v.valor_adquisicion || 0;

    openModal('modalEditarVehiculo');
}

const formEditarVehiculo = document.getElementById('formEditarVehiculo');
if (formEditarVehiculo) {
    formEditarVehiculo.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('action', 'edit_activo');
        formData.append('id', currentVehiculoId);
        formData.append('categoria', document.getElementById('evnCategoria').value);
        formData.append('nombre', document.getElementById('evnNombre').value.trim());
        formData.append('placa', document.getElementById('evnPlaca').value.trim());
        formData.append('codigo_identificador', document.getElementById('evnPlaca').value.trim());
        formData.append('tipo', document.getElementById('evnTipo').value.trim());
        formData.append('marca', document.getElementById('evnMarca').value.trim());
        formData.append('modelo', document.getElementById('evnModelo').value.trim());
        formData.append('ubicacion', document.getElementById('evnUbicacion').value.trim());
        formData.append('responsable_nombre', document.getElementById('evnResponsable').value.trim());
        formData.append('estado', document.getElementById('evnEstado').value);
        formData.append('valor_adquisicion', document.getElementById('evnValor').value);

        try {
            const res = await fetch(`${baseUrl}/ajax/activos.php`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                closeModal('modalEditarVehiculo');
                await loadVehicles();
                openAssetDetails(currentVehiculoId);
            } else {
                alert('Error al editar activo: ' + (data.error || 'Error'));
            }
        } catch (err) {
            console.error(err);
            alert('Error en servidor al actualizar activo.');
        }
    });
}

async function archiveVehicle() {
    if (!confirm('¿Estás seguro de archivar este activo? No se borrará del historial financiero.')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'delete_vehiculo');
        formData.append('id', currentVehiculoId);

        const res = await fetch(`${baseUrl}/ajax/activos.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            closeModal('modalEditarVehiculo');
            closeModal('modalDetalleVehiculo');
            await loadVehicles();
        } else {
            alert('Error al archivar: ' + data.error);
        }
    } catch (err) {
        console.error(err);
    }
}

// ==========================================================
// MODAL: SUBIR DOCUMENTO
// ==========================================================
function openUploadDocModal() {
    document.getElementById('formSubirDoc').reset();
    openModal('modalSubirDoc');
}

const formSubirDoc = document.getElementById('formSubirDoc');
if (formSubirDoc) {
    formSubirDoc.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(formSubirDoc);
        formData.append('action', 'upload_doc');
        formData.append('vehiculo_id', currentVehiculoId);
        formData.append('tipo_documento', document.getElementById('docTipo').value);
        formData.append('titulo', document.getElementById('docTitulo').value);

        try {
            const res = await fetch(`${baseUrl}/ajax/activos.php`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                closeModal('modalSubirDoc');
                openAssetDetails(currentVehiculoId);
            } else {
                alert('Error: ' + data.error);
            }
        } catch (err) {
            console.error(err);
        }
    });
}

function openEditDocModal(docId, tipo, titulo) {
    document.getElementById('edDocId').value = docId;
    document.getElementById('edDocTipo').value = tipo;
    document.getElementById('edDocTitulo').value = titulo;
    openModal('modalEditarDoc');
}

const formEditarDoc = document.getElementById('formEditarDoc');
if (formEditarDoc) {
    formEditarDoc.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('action', 'edit_doc');
        formData.append('doc_id', document.getElementById('edDocId').value);
        formData.append('tipo_documento', document.getElementById('edDocTipo').value);
        formData.append('titulo', document.getElementById('edDocTitulo').value);

        try {
            const res = await fetch(`${baseUrl}/ajax/activos.php`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                closeModal('modalEditarDoc');
                openAssetDetails(currentVehiculoId);
            } else {
                alert('Error: ' + data.error);
            }
        } catch (err) {
            console.error(err);
        }
    });
}

async function deleteDoc(docId) {
    if (!confirm('¿Deseas eliminar este documento?')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'delete_doc');
        formData.append('doc_id', docId);

        const res = await fetch(`${baseUrl}/ajax/activos.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            openAssetDetails(currentVehiculoId);
        } else {
            alert('Error: ' + data.error);
        }
    } catch (err) {
        console.error(err);
    }
}

// ==========================================================
// MODAL: SUBIR FOTO A GALERÍA
// ==========================================================
function openUploadImageModal() {
    document.getElementById('formSubirFoto').reset();
    openModal('modalSubirFoto');
}

const formSubirFoto = document.getElementById('formSubirFoto');
if (formSubirFoto) {
    formSubirFoto.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(formSubirFoto);
        formData.append('action', 'upload_foto');
        formData.append('vehiculo_id', currentVehiculoId);
        formData.append('descripcion', document.getElementById('fotoDesc').value);

        try {
            const res = await fetch(`${baseUrl}/ajax/activos.php`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                closeModal('modalSubirFoto');
                openAssetDetails(currentVehiculoId);
            } else {
                alert('Error: ' + data.error);
            }
        } catch (err) {
            console.error(err);
        }
    });
}

// ==========================================================
// MODAL: REGISTRAR EVENTO / MANTENIMIENTO
// ==========================================================
function openNewEventModal(tipo) {
    document.getElementById('formRegistrarEvento').reset();
    document.getElementById('evTipoEvento').value = tipo;

    const tituloEl = document.getElementById('tituloModalEvento');
    if (tituloEl) {
        tituloEl.innerText = tipo === 'cambio_llantas' ? 'Registrar Cambio de Llantas' : 'Registrar Servicio / Mantenimiento';
    }

    const today = new Date().toISOString().split('T')[0];
    document.getElementById('evFecha').value = today;

    openModal('modalRegistrarEvento');
}

const formRegistrarEvento = document.getElementById('formRegistrarEvento');
if (formRegistrarEvento) {
    formRegistrarEvento.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(formRegistrarEvento);
        formData.append('action', 'save_evento');
        formData.append('vehiculo_id', currentVehiculoId);
        formData.append('tipo_evento', document.getElementById('evTipoEvento').value);
        formData.append('fecha_evento', document.getElementById('evFecha').value);
        formData.append('costo', document.getElementById('evCosto').value || 0);
        formData.append('descripcion', document.getElementById('evDesc').value);

        try {
            const res = await fetch(`${baseUrl}/ajax/activos.php`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                closeModal('modalRegistrarEvento');
                openAssetDetails(currentVehiculoId);
            } else {
                alert('Error: ' + data.error);
            }
        } catch (err) {
            console.error(err);
        }
    });
}

// ==========================================================
// HELPERS MODALES ROBUSTOS
// ==========================================================
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
        modal.classList.add('show');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
        modal.classList.remove('show');
        modal.style.display = 'none';
        const anyOpen = document.querySelectorAll('.modal-overlay.active, .modal-overlay.show');
        if (anyOpen.length === 0) {
            document.body.style.overflow = '';
        }
    }
}

// Cerrar al hacer clic en el fondo oscuro
document.addEventListener('click', (event) => {
    if (event.target && event.target.classList && event.target.classList.contains('modal-overlay')) {
        closeModal(event.target.id);
    }
});

function escapeQuotes(str) {
    return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
}
