<?php
// Módulo Dashboard en blanco
?>
<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="ph ph-chart-pie"></i></div>
        <div class="page-header-info">
            <h2>Dashboard de Inventario</h2>
            <p>Métricas de uso, stock y estado de los productos</p>
        </div>
    </div>
</div>

<div class="settings-section mt-4 mb-4" style="padding: 20px;">
    <form id="dashboard-filter-form" class="row gx-3 gy-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label" for="filter_start_date">Fecha de Inicio</label>
            <input type="date" class="form-control" id="filter_start_date" name="start_date" value="<?php echo date('Y-m-01'); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="filter_end_date">Fecha de Fin</label>
            <input type="date" class="form-control" id="filter_end_date" name="end_date" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100"><i class="ph ph-funnel"></i> Aplicar Filtros</button>
        </div>
    </form>
</div>

<!-- Gráficos -->
<div class="row gx-4 gy-4 dashboard-charts-container">
    <!-- Estados (Pie Chart) -->
    <div class="col-md-6">
        <div class="settings-section" style="padding: 20px; height: 100%;">
            <h4 class="mb-3">Estado Histórico (Filtro Aplicado)</h4>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="chart-status"></canvas>
            </div>
        </div>
    </div>
    <!-- Productos Menos Stock (Bar Horizontal) -->
    <div class="col-md-6">
        <div class="settings-section" style="padding: 20px; height: 100%;">
            <h4 class="mb-3">Top 5: Menos Stock (Actual)</h4>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="chart-low-stock"></canvas>
            </div>
        </div>
    </div>

    <!-- Productos Más Usados (Bar) -->
    <div class="col-md-6">
        <div class="settings-section" style="padding: 20px; height: 100%;">
            <h4 class="mb-3">Top 5: Más Usados</h4>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="chart-most-used"></canvas>
            </div>
        </div>
    </div>

    <!-- Productos Menos Usados (Bar) -->
    <div class="col-md-6">
        <div class="settings-section" style="padding: 20px; height: 100%;">
            <h4 class="mb-3">Top 5: Menos Usados</h4>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="chart-least-used"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Scripts requeridos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/modules/inventario/dashboard/dashboard.js"></script>
