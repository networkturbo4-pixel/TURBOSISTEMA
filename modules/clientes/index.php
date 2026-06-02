<?php
require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'clientes');

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-header-icon">
            <i class="ph ph-users"></i>
        </div>
        <div class="page-header-info">
            <h2>Clientes</h2>
            <p>Gestiona el registro y visualización de todos tus clientes.</p>
        </div>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-outline-primary" style="margin-right: 10px;" id="btnGestionarServicios">
            <i class="ph ph-list-dashes"></i> Gestionar Servicios
        </button>
        <button type="button" class="btn btn-primary" id="btnNewCliente">
            <i class="ph ph-plus"></i> Nuevo Cliente
        </button>
    </div>
</div>

<div class="settings-section">
    <!-- Buscador con Filtros Inteligentes -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="search-box" style="position: relative; display: flex; align-items: center;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 16px; color: var(--text-muted); font-size: 1.2rem; z-index: 5;"></i>
                <input type="text" id="searchInput" class="form-control w-100" placeholder="Buscador inteligente por Nombre, DNI o Plan Contratado..." style="padding-left: 45px; height: 48px; border-radius: 12px; position: relative; z-index: 1;">
            </div>
        </div>
    </div>

    <!-- Tabla de Clientes -->
    <div class="table-responsive">
        <table class="table table-hover" id="clientesTable">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>DNI</th>
                    <th>Celular</th>
                    <th>Plan Contratado</th>
                    <th>Inicio Servicio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Dinámico vía AJAX -->
                <tr><td colspan="7" class="text-center">Cargando clientes...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear/Editar Cliente -->
<div class="modal-overlay" id="clienteModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="clienteModalTitle">Nuevo Cliente</h3>
            <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer;" onclick="document.getElementById('clienteModal').classList.remove('active')">&times;</button>
        </div>
        <form id="clienteForm" style="display: flex; flex-direction: column; overflow: hidden;">
            <input type="hidden" name="id" id="cliente_id_input">
            <input type="hidden" name="latitud" id="cliente_latitud">
            <input type="hidden" name="longitud" id="cliente_longitud">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_completo" class="form-control" required placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label d-flex justify-content-between">DNI / RUC <span class="text-danger">*</span> <button type="button" id="btnSearchClienteReniec" style="background:none; border:none; color:var(--primary-color); font-size:0.8rem; font-weight:bold; cursor:pointer; padding:0;"><i class="ph ph-magnifying-glass"></i> <span id="textClienteReniec">Buscar</span></button></label>
                        <input type="text" name="dni" id="cliente_dni" class="form-control" required placeholder="Ej: 12345678">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Número de Celular</label>
                        <input type="text" name="celular" class="form-control" placeholder="Ej: 999888777">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="correo" class="form-control" placeholder="Ej: juan@email.com">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control" placeholder="Ej: Av. Principal 123">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Referencia</label>
                        <input type="text" name="referencia" class="form-control" placeholder="Ej: Frente al parque">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Servicio Contratado</label>
                        <select name="servicio_id" id="servicio_id" class="form-select">
                            <option value="">Sin plan</option>
                            <!-- Cargado dinámicamente -->
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Ubicación Exacta (Google Maps)</label>
                        <div id="cliente_map" style="width: 100%; height: 300px; border-radius: 8px; border: 1px solid var(--border-color); background: #eee;"></div>
                        <small class="text-muted">Arrastra el marcador o haz clic en el mapa para establecer la ubicación.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha y Hora Servicio Contratado</label>
                        <input type="datetime-local" name="fecha_servicio_contratado" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Inicio de Servicio</label>
                        <input type="datetime-local" name="inicio_servicio" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('clienteModal').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Gestionar Servicios -->
<div class="modal-overlay" id="serviciosModal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3>Gestionar Servicios</h3>
            <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer;" onclick="document.getElementById('serviciosModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <button class="btn btn-primary mb-3" id="btnNewServicio"><i class="ph ph-plus"></i> Nuevo Servicio</button>
            <div class="table-responsive">
                <table class="table table-hover" id="serviciosTable">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Velocidad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dinámico vía AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Formulario Servicio -->
<div class="modal-overlay" id="servicioFormModal" style="z-index: 9999;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 id="servicioModalTitle">Nuevo Servicio</h3>
            <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer;" onclick="document.getElementById('servicioFormModal').classList.remove('active')">&times;</button>
        </div>
        <form id="servicioForm">
            <input type="hidden" name="id" id="servicio_id_input">
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre del Servicio <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Velocidad</label>
                    <input type="text" name="velocidad" class="form-control" placeholder="Ej: 700 Mbps">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('servicioFormModal').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Google Maps API -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBNXLdtgdStVUGqNeDFdaHRTpCjaVHF6RE&libraries=places"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    window.clientesData = [];

    const renderTable = (data) => {
        const tbody = document.querySelector('#clientesTable tbody');
        tbody.innerHTML = '';
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No se encontraron clientes.</td></tr>';
            return;
        }

        data.forEach(cliente => {
            // Formatear fechas si existen
            let inicioSrv = cliente.inicio_servicio ? new Date(cliente.inicio_servicio).toLocaleString() : 'N/A';
            
            tbody.innerHTML += `
                <tr>
                    <td data-label="Nombre Completo" class="fw-500">${cliente.nombre_completo}</td>
                    <td data-label="DNI">${cliente.dni}</td>
                    <td data-label="Celular">${cliente.celular || '-'}</td>
                    <td data-label="Plan Contratado"><span class="tag-pill">${cliente.servicio_nombre || cliente.detalles_plan || 'Sin plan'}</span></td>
                    <td data-label="Inicio Servicio">${inicioSrv}</td>
                    <td data-label="Acciones">
                        <button type="button" class="btn btn-sm btn-outline-primary" style="padding: 2px 8px; font-size: 0.8rem;" onclick="editCliente(${cliente.id})">Editar</button>
                        <button type="button" class="table-btn-danger" onclick="deleteCliente(${cliente.id})">Eliminar</button>
                    </td>
                </tr>
            `;
        });
    };

    const loadClientes = async () => {
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/clientes.php?action=list').then(r => r.json());
            if (res.success) {
                window.clientesData = res.data;
                renderTable(res.data);
            }
        } catch (e) {
            console.error(e);
            window.showToast('Error al cargar clientes', 'error');
        }
    };

    // Buscador Inteligente
    document.getElementById('searchInput').addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        const filtered = window.clientesData.filter(c => {
            return (c.nombre_completo && c.nombre_completo.toLowerCase().includes(term)) ||
                   (c.dni && c.dni.toLowerCase().includes(term)) ||
                   (c.servicio_nombre && c.servicio_nombre.toLowerCase().includes(term)) ||
                   (c.detalles_plan && c.detalles_plan.toLowerCase().includes(term));
        });
        renderTable(filtered);
    });

    window.editCliente = (id) => {
        const cliente = window.clientesData.find(c => c.id == id);
        if(!cliente) return;
        
        document.getElementById('clienteModalTitle').innerText = 'Editar Cliente';
        document.getElementById('cliente_id_input').value = cliente.id;
        
        // Poblar campos
        const form = document.getElementById('clienteForm');
        form.querySelector('[name="nombre_completo"]').value = cliente.nombre_completo;
        form.querySelector('[name="dni"]').value = cliente.dni;
        form.querySelector('[name="celular"]').value = cliente.celular || '';
        form.querySelector('[name="correo"]').value = cliente.correo || '';
        form.querySelector('[name="direccion"]').value = cliente.direccion || '';
        form.querySelector('[name="referencia"]').value = cliente.referencia || '';
        form.querySelector('[name="servicio_id"]').value = cliente.servicio_id || '';
        form.querySelector('[name="latitud"]').value = cliente.latitud || '';
        form.querySelector('[name="longitud"]').value = cliente.longitud || '';
        
        // Ajustar el formato de datetime-local a YYYY-MM-DDTHH:MM
        const formatForInput = (dateStr) => {
            if(!dateStr) return '';
            // Si viene de BD como 'YYYY-MM-DD HH:MM:SS' hay que cambiar el espacio por 'T' y quitar segundos.
            let parts = dateStr.split(' ');
            if(parts.length === 2) {
                return parts[0] + 'T' + parts[1].substring(0,5);
            }
            return dateStr;
        };

        form.querySelector('[name="fecha_servicio_contratado"]').value = formatForInput(cliente.fecha_servicio_contratado);
        form.querySelector('[name="inicio_servicio"]').value = formatForInput(cliente.inicio_servicio);
        
        document.getElementById('clienteModal').classList.add('active');
        
        // Initialize map after modal is shown
        setTimeout(() => {
            initMap(cliente.latitud, cliente.longitud);
        }, 300);
    };

    window.deleteCliente = (id) => {
        window.showGlobalDeleteModal(async () => {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            try {
                const res = await fetch('<?php echo BASE_URL; ?>/ajax/clientes.php', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json());
                if (res.success) {
                    window.showToast(res.message, 'success');
                    loadClientes();
                } else {
                    window.showToast(res.message, 'error');
                }
            } catch (error) {
                console.error(error);
                window.showToast('Error en el servidor', 'error');
            }
        });
    };

    document.getElementById('btnNewCliente').addEventListener('click', () => {
        document.getElementById('clienteForm').reset();
        document.getElementById('cliente_id_input').value = '';
        document.getElementById('cliente_latitud').value = '';
        document.getElementById('cliente_longitud').value = '';
        document.getElementById('clienteModalTitle').innerText = 'Nuevo Cliente';
        document.getElementById('clienteModal').classList.add('active');
        
        // Initialize map after modal is shown
        setTimeout(() => {
            initMap();
        }, 300);
    });

    document.getElementById('clienteForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        // La acción siempre será 'save'. En el backend, verificará si viene con ID.
        formData.append('action', 'save');

        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/clientes.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json());
            if (res.success) {
                window.showToast(res.message, 'success');
                document.getElementById('clienteModal').classList.remove('active');
                e.target.reset();
                loadClientes();
            } else {
                window.showToast(res.message, 'error');
            }
        } catch (error) {
            console.error(error);
            window.showToast('Error en el servidor', 'error');
        }
    });

    // --- BUSQUEDA RENIEC/SUNAT ---
    const btnSearchClienteReniec = document.getElementById('btnSearchClienteReniec');
    if (btnSearchClienteReniec) {
        btnSearchClienteReniec.addEventListener('click', async () => {
            const docInput = document.getElementById('cliente_dni');
            const docNumber = docInput.value.trim();
            
            if (docNumber.length !== 8 && docNumber.length !== 11) {
                window.showToast('El documento debe tener 8 (DNI) u 11 (RUC) dígitos', 'error');
                return;
            }
            
            const originalHtml = btnSearchClienteReniec.innerHTML;
            btnSearchClienteReniec.innerHTML = '<i class="ph ph-spinner fa-spin"></i>';
            btnSearchClienteReniec.disabled = true;
            
            try {
                const res = await fetch(`<?php echo BASE_URL; ?>/ajax/api_peru.php?doc=${docNumber}`).then(r => r.json());
                
                if (res.success && res.data && res.data.success !== false) {
                    const data = res.data.data;
                    const nombreInput = document.querySelector('#clienteForm input[name="nombre_completo"]');
                    
                    if (res.type === 'DNI') {
                        nombreInput.value = data.nombre_completo;
                        const dirInput = document.querySelector('#clienteForm input[name="direccion"]');
                        if (dirInput && data.direccion) {
                            dirInput.value = data.direccion;
                        } else if (dirInput && data.direccion_completa) {
                            dirInput.value = data.direccion_completa;
                        }
                        window.showToast('Datos obtenidos correctamente', 'success');
                    } else if (res.type === 'RUC') {
                        nombreInput.value = data.nombre_o_razon_social;
                        
                        // Llenar dirección si existe
                        const dirInput = document.querySelector('#clienteForm input[name="direccion"]');
                        if (dirInput && data.direccion) dirInput.value = data.direccion;
                        
                        window.showToast('Datos de SUNAT obtenidos', 'success');
                    }
                } else {
                    window.showToast(res.data ? res.data.message : res.message || 'No se encontraron resultados', 'error');
                }
            } catch (error) {
                console.error(error);
                window.showToast('Error de conexión', 'error');
            } finally {
                btnSearchClienteReniec.innerHTML = originalHtml;
                btnSearchClienteReniec.disabled = false;
            }
        });
    }

    // --- MAPA DE GOOGLE MAPS ---
    let map;
    let marker;

    const initMap = (lat, lng) => {
        if (!lat || !lng) {
            lat = -12.046374; // Default Lima
            lng = -77.042793;
        }
        lat = parseFloat(lat);
        lng = parseFloat(lng);

        const mapOptions = {
            center: { lat, lng },
            zoom: 15,
            mapTypeControl: false,
            streetViewControl: false
        };

        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            console.error('Google Maps API no cargada.');
            return;
        }

        if (!map) {
            map = new google.maps.Map(document.getElementById('cliente_map'), mapOptions);
            marker = new google.maps.Marker({
                position: { lat, lng },
                map: map,
                draggable: true
            });

            google.maps.event.addListener(marker, 'dragend', function (evt) {
                document.getElementById('cliente_latitud').value = evt.latLng.lat();
                document.getElementById('cliente_longitud').value = evt.latLng.lng();
            });

            google.maps.event.addListener(map, 'click', function (evt) {
                marker.setPosition(evt.latLng);
                document.getElementById('cliente_latitud').value = evt.latLng.lat();
                document.getElementById('cliente_longitud').value = evt.latLng.lng();
            });
        } else {
            map.setCenter({ lat, lng });
            marker.setPosition({ lat, lng });
        }
        
        document.getElementById('cliente_latitud').value = lat;
        document.getElementById('cliente_longitud').value = lng;
    };

    // --- SERVICIOS CRUD ---
    const loadServiciosDropdown = async () => {
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/servicios.php?action=list').then(r => r.json());
            if (res.success) {
                const select = document.getElementById('servicio_id');
                if (select) {
                    const prevVal = select.value;
                    select.innerHTML = '<option value="">Sin plan</option>';
                    res.data.forEach(srv => {
                        select.innerHTML += `<option value="${srv.id}">${srv.nombre} ${srv.velocidad ? '('+srv.velocidad+')' : ''}</option>`;
                    });
                    select.value = prevVal; // Restore previous selection if possible
                }
                return res.data;
            }
        } catch (e) {
            console.error(e);
        }
        return [];
    };

    const renderServiciosTable = (data) => {
        const tbody = document.querySelector('#serviciosTable tbody');
        tbody.innerHTML = '';
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay servicios registrados.</td></tr>';
            return;
        }
        data.forEach(srv => {
            tbody.innerHTML += `
                <tr>
                    <td>${srv.nombre}</td>
                    <td>${srv.descripcion || '-'}</td>
                    <td>${srv.velocidad || '-'}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick='editServicio(${JSON.stringify(srv).replace(/'/g, "&#39;")})'>Editar</button>
                        <button type="button" class="table-btn-danger" onclick="deleteServicio(${srv.id})">Eliminar</button>
                    </td>
                </tr>
            `;
        });
    };

    window.editServicio = (srv) => {
        document.getElementById('servicioModalTitle').innerText = 'Editar Servicio';
        document.getElementById('servicio_id_input').value = srv.id;
        document.getElementById('servicioForm').querySelector('[name="nombre"]').value = srv.nombre;
        document.getElementById('servicioForm').querySelector('[name="descripcion"]').value = srv.descripcion || '';
        document.getElementById('servicioForm').querySelector('[name="velocidad"]').value = srv.velocidad || '';
        document.getElementById('servicioFormModal').classList.add('active');
    };

    window.deleteServicio = (id) => {
        window.showGlobalDeleteModal(async () => {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            try {
                const res = await fetch('<?php echo BASE_URL; ?>/ajax/servicios.php', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json());
                if (res.success) {
                    window.showToast(res.message, 'success');
                    loadServiciosModalData();
                } else {
                    window.showToast(res.message, 'error');
                }
            } catch (error) {
                window.showToast('Error en el servidor', 'error');
            }
        });
    };

    const loadServiciosModalData = async () => {
        const data = await loadServiciosDropdown();
        renderServiciosTable(data);
    };

    const btnGestionarServicios = document.getElementById('btnGestionarServicios');
    if (btnGestionarServicios) {
        btnGestionarServicios.addEventListener('click', () => {
            document.getElementById('serviciosModal').classList.add('active');
            loadServiciosModalData();
        });
    }

    const btnNewServicio = document.getElementById('btnNewServicio');
    if (btnNewServicio) {
        btnNewServicio.addEventListener('click', () => {
            document.getElementById('servicioForm').reset();
            document.getElementById('servicio_id_input').value = '';
            document.getElementById('servicioModalTitle').innerText = 'Nuevo Servicio';
            document.getElementById('servicioFormModal').classList.add('active');
        });
    }

    const servicioForm = document.getElementById('servicioForm');
    if (servicioForm) {
        servicioForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'save');
            try {
                const res = await fetch('<?php echo BASE_URL; ?>/ajax/servicios.php', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json());
                if (res.success) {
                    window.showToast(res.message, 'success');
                    document.getElementById('servicioFormModal').classList.remove('active');
                    loadServiciosModalData();
                } else {
                    window.showToast(res.message, 'error');
                }
            } catch (error) {
                window.showToast('Error en el servidor', 'error');
            }
        });
    }

    // Carga inicial
    loadClientes();
    loadServiciosDropdown();
});
</script>

<?php include '../../includes/footer.php'; ?>
