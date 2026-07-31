document.addEventListener('DOMContentLoaded', () => {
    
    // Variables para guardar las instancias de los gráficos
    let chartMostUsed = null;
    let chartLeastUsed = null;
    let chartStatus = null;
    let chartLowStock = null;
    let chartValueEvol = null;
    let chartReturns = null;
    let chartTopTechs = null;
    let sparkCharts = {};

    const form = document.getElementById('dashboard-filter-form');

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        loadDashboardData();
    });

    // Tabs Internas
    document.querySelectorAll('.d-tab-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.d-tab-btn').forEach(b => { b.classList.remove('active', 'btn-primary'); b.classList.add('btn-outline-primary'); });
            e.currentTarget.classList.remove('btn-outline-primary');
            e.currentTarget.classList.add('active', 'btn-primary');
            
            const targetId = 'dtab-' + e.currentTarget.dataset.dtab;
            document.querySelectorAll('.d-tab-content').forEach(c => {
                c.style.display = 'none';
                c.classList.remove('active');
            });
            document.getElementById(targetId).style.display = 'block';
            document.getElementById(targetId).classList.add('active');
        });
    });

    // Filtros Rápidos
    window.setDashFilter = function(type) {
        const today = new Date();
        const end = today.toISOString().split('T')[0];
        let start = end;
        if(type === 'week') {
            const lastWeek = new Date(today);
            lastWeek.setDate(today.getDate() - 7);
            start = lastWeek.toISOString().split('T')[0];
        } else if(type === 'month') {
            start = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-01';
        } else if(type === 'year') {
            const lastYear = new Date(today);
            lastYear.setFullYear(today.getFullYear() - 1);
            start = lastYear.toISOString().split('T')[0];
        }
        document.getElementById('filter_start_date').value = start;
        document.getElementById('filter_end_date').value = end;
        loadDashboardData();
    };

    // Formateador de moneda (Regla global)
    const appCurrency = (window.appSettings && window.appSettings.currency) ? window.appSettings.currency : 'USD';
    const currencyLocales = {
        'USD': 'en-US',
        'EUR': 'es-ES',
        'MXN': 'es-MX',
        'COP': 'es-CO',
        'PEN': 'es-PE'
    };
    const appLocale = currencyLocales[appCurrency] || 'en-US';
    const formatMoney = (val) => new Intl.NumberFormat(appLocale, { style: 'currency', currency: appCurrency }).format(val);

    // Carga inicial
    loadDashboardData();

    function loadDashboardData() {
        const formData = new FormData(form);
        formData.append('action', 'get_dashboard_data');
        const BASE = document.querySelector('meta[name="base-url"]')?.content || (window.location.pathname.includes('/TURBOSAAS') ? '/TURBOSAAS' : '');

        fetch(BASE + '/ajax/dashboard_inventario.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                renderCharts(res.data);
                
                if (res.data.metrics) {
                    const m = res.data.metrics;
                    const updateMetric = (id, data, format = false) => {
                        const el = document.getElementById(id);
                        if(el) el.textContent = format ? formatMoney(data.current) : data.current;
                        
                        const trendEl = document.getElementById(id.replace('metric', 'trend'));
                        if(trendEl && data.trend !== undefined) {
                            if(data.trend > 0) {
                                trendEl.innerHTML = `<span style="color:#10b981;">(↑ ${data.trend}%)</span>`;
                            } else if(data.trend < 0) {
                                trendEl.innerHTML = `<span style="color:#ef4444;">(↓ ${Math.abs(data.trend)}%)</span>`;
                            } else {
                                trendEl.innerHTML = `<span style="color:#64748b;">(0%)</span>`;
                            }
                        }
                    };

                    updateMetric('metricProductos', m.productos_registrados);
                    updateMetric('metricTotal', m.total_unidades);
                    updateMetric('metricDisponible', m.disponibles);
                    updateMetric('metricInstalado', m.instalados);
                    
                    if(document.getElementById('metricLowStock')) document.getElementById('metricLowStock').textContent = res.data.lowest_stock.length;
                    
                    updateMetric('metricValorTotal', m.valor_total, true);
                    updateMetric('metricCapitalInmovilizado', m.capital_inmovilizado, true);
                }
            } else {
                if (window.showToast) window.showToast("Error: " + res.message, "error");
            }
        })
        .catch(err => {
            console.error("Error en petición de dashboard:", err);
        });
    }

    function isDarkMode() { return document.body.classList.contains('dark-theme'); }
    function getTextColor() { return isDarkMode() ? '#f8fafc' : '#334155'; }
    function getGridColor() { return isDarkMode() ? '#334155' : '#e2e8f0'; }

    function destroyChart(chartInstance) {
        if (chartInstance) chartInstance.destroy();
    }

    // HTML Tooltip Custom
    const externalTooltipHandler = (context) => {
        const {chart, tooltip} = context;
        let tooltipEl = document.getElementById('chartjs-tooltip');

        if (tooltip.opacity === 0) {
            tooltipEl.style.opacity = 0;
            return;
        }

        if (tooltip.body) {
            const titleLines = tooltip.title || [];
            const bodyLines = tooltip.body.map(b => b.lines);

            let innerHtml = '<thead>';
            titleLines.forEach(title => {
                innerHtml += `<tr><th style="border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:5px; margin-bottom:5px;">${title}</th></tr>`;
            });
            innerHtml += '</thead><tbody>';

            bodyLines.forEach((body, i) => {
                const colors = tooltip.labelColors[i];
                const colorSquare = `<span style="display:inline-block; width:10px; height:10px; margin-right:5px; background:${colors.backgroundColor}; border:${colors.borderWidth}px solid ${colors.borderColor};"></span>`;
                innerHtml += `<tr><td style="padding-top:5px;">${colorSquare}${body}</td></tr>`;
            });
            
            // Custom Hint para Drilldown
            if(chart.config.options.onClick) {
                innerHtml += `<tr><td style="padding-top:8px; font-size:11px; color:#94a3b8;"><i class="ph ph-hand-pointing"></i> Clic para ver historial</td></tr>`;
            }

            innerHtml += '</tbody>';
            tooltipEl.querySelector('table').innerHTML = innerHtml;
        }

        const position = chart.canvas.getBoundingClientRect();
        let left = position.left + window.pageXOffset + tooltip.caretX;
        let top = position.top + window.pageYOffset + tooltip.caretY;

        // Prevent horizontal scroll by keeping tooltip within viewport
        tooltipEl.style.opacity = 1;
        
        // Use a small timeout to allow tooltip to render and get offsetWidth
        requestAnimationFrame(() => {
            if (left + tooltipEl.offsetWidth > window.innerWidth - 20) {
                left = window.innerWidth - tooltipEl.offsetWidth - 20;
            }
            tooltipEl.style.left = left + 'px';
            tooltipEl.style.top = top + 'px';
        });
    };

    function createSparkline(canvasId, data, color) {
        const ctx = document.getElementById(canvasId);
        if(!ctx) return;
        if(sparkCharts[canvasId]) sparkCharts[canvasId].destroy();
        
        sparkCharts[canvasId] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map((_, i) => i),
                datasets: [{
                    data: data,
                    borderColor: color,
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false, min: 0 } },
                layout: { padding: 0 }
            }
        });
    }

    function renderCharts(data) {
        const textColor = getTextColor();
        const gridColor = getGridColor();

        Chart.defaults.color = textColor;
        Chart.defaults.font.family = 'Inter, Roboto, sans-serif';

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false, external: externalTooltipHandler }
            },
            scales: {
                x: { ticks: { color: textColor, font: { size: 11 } }, grid: { color: gridColor, drawBorder: false, borderDash: [5, 5] }, border: { display: false } },
                y: { ticks: { color: textColor, font: { size: 11 } }, grid: { color: gridColor, drawBorder: false, borderDash: [5, 5] }, border: { display: false } }
            },
            layout: { padding: { top: 10, right: 10, bottom: 10, left: 10 } }
        };

        // Drill-Down onClick handler
        const onBarClick = (e, elements, chart) => {
            if (elements.length > 0) {
                const index = elements[0].index;
                const productName = chart.data.labels[index];
                if(productName !== 'Sin Datos') openDrillDown(productName);
            }
        };

        // --- SPARK LINES ---
        // Generamos data falsa bonita para las sparklines solo por estética (como se sugirió)
        const generateSparkData = () => Array.from({length: 10}, () => Math.floor(Math.random() * 40) + 10);
        createSparkline('spark-productos', generateSparkData(), '#ec4899');
        createSparkline('spark-total', generateSparkData(), '#6366f1');
        createSparkline('spark-disponible', generateSparkData(), '#10b981');
        createSparkline('spark-instalado', generateSparkData(), '#3b82f6');


        // 1. Estados (Doughnut)
        destroyChart(chartStatus);
        const ctxStatus = document.getElementById('chart-status')?.getContext('2d');
        if(ctxStatus) {
            const statusData = data.status_stats;
            const totalStatus = statusData.disponible + statusData.instalado + statusData.malogrado + statusData.reparado + statusData.observacion;
            const statusLabels = ['Disponible', 'Instalado', 'Malogrado', 'Reparado', 'Observación'];
            let statusValues = [statusData.disponible, statusData.instalado, statusData.malogrado, statusData.reparado, statusData.observacion];
            let statusColors = ['#10b981', '#3b82f6', '#ef4444', '#f59e0b', '#8b5cf6'];
            
            if (totalStatus === 0) {
                statusLabels.push('Sin Datos');
                statusValues = [0, 0, 0, 0, 0, 1];
                statusColors = ['#10b981', '#3b82f6', '#ef4444', '#f59e0b', '#8b5cf6', isDarkMode() ? '#334155' : '#e2e8f0'];
            }

            chartStatus = new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusValues,
                        backgroundColor: statusColors,
                        borderWidth: 2,
                        borderColor: isDarkMode() ? '#1e293b' : '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '75%',
                    plugins: {
                        legend: { display: true, position: 'bottom', labels: { color: textColor, padding: 20, usePointStyle: true, pointStyle: 'circle', font: { size: 12 } } },
                        tooltip: { enabled: false, external: externalTooltipHandler }
                    },
                    layout: { padding: 10 }
                }
            });
        }

        // 2. Menos Stock (Barra Horizontal)
        destroyChart(chartLowStock);
        const ctxLowStock = document.getElementById('chart-low-stock')?.getContext('2d');
        if(ctxLowStock) {
            const lowStockLabels = data.lowest_stock.length > 0 ? data.lowest_stock.map(i => i.product_name) : ['Sin Datos'];
            const lowStockValues = data.lowest_stock.length > 0 ? data.lowest_stock.map(i => i.stock) : [0];
            
            const gradientRed = ctxLowStock.createLinearGradient(0, 0, 400, 0);
            gradientRed.addColorStop(0, '#f43f5e'); gradientRed.addColorStop(1, '#fb7185');

            chartLowStock = new Chart(ctxLowStock, {
                type: 'bar',
                data: {
                    labels: lowStockLabels,
                    datasets: [{ label: 'Stock Actual', data: lowStockValues, backgroundColor: data.lowest_stock.length > 0 ? gradientRed : (isDarkMode() ? '#334155' : '#e2e8f0'), borderRadius: 6, barPercentage: 0.5 }]
                },
                options: { ...commonOptions, indexAxis: 'y', onClick: onBarClick }
            });
        }

        // 3. Más Usados (Barra Vertical)
        destroyChart(chartMostUsed);
        const ctxMostUsed = document.getElementById('chart-most-used')?.getContext('2d');
        if(ctxMostUsed) {
            const mostUsedLabels = data.most_used.length > 0 ? data.most_used.map(i => i.product_name) : ['Sin Datos'];
            const mostUsedValues = data.most_used.length > 0 ? data.most_used.map(i => parseInt(i.total_used)) : [0];

            const gradientBlue = ctxMostUsed.createLinearGradient(0, 0, 0, 400);
            gradientBlue.addColorStop(0, '#6366f1'); gradientBlue.addColorStop(1, '#818cf8');

            chartMostUsed = new Chart(ctxMostUsed, {
                type: 'bar',
                data: { labels: mostUsedLabels, datasets: [{ label: 'Asignaciones', data: mostUsedValues, backgroundColor: data.most_used.length > 0 ? gradientBlue : (isDarkMode() ? '#334155' : '#e2e8f0'), borderRadius: 6, barPercentage: 0.5 }] },
                options: { ...commonOptions, onClick: onBarClick }
            });
        }

        // 4. Menos Usados (Barra Vertical)
        destroyChart(chartLeastUsed);
        const ctxLeastUsed = document.getElementById('chart-least-used')?.getContext('2d');
        if(ctxLeastUsed) {
            const leastUsedLabels = data.least_used.length > 0 ? data.least_used.map(i => i.product_name) : ['Sin Datos'];
            const leastUsedValues = data.least_used.length > 0 ? data.least_used.map(i => parseInt(i.total_used)) : [0];

            const gradientAmber = ctxLeastUsed.createLinearGradient(0, 0, 0, 400);
            gradientAmber.addColorStop(0, '#f59e0b'); gradientAmber.addColorStop(1, '#fbbf24');

            chartLeastUsed = new Chart(ctxLeastUsed, {
                type: 'bar',
                data: { labels: leastUsedLabels, datasets: [{ label: 'Asignaciones', data: leastUsedValues, backgroundColor: data.least_used.length > 0 ? gradientAmber : (isDarkMode() ? '#334155' : '#e2e8f0'), borderRadius: 6, barPercentage: 0.5 }] },
                options: { ...commonOptions, onClick: onBarClick }
            });
        }

        // 5. Evolución del Valor (Líneas)
        destroyChart(chartValueEvol);
        const ctxValueEvol = document.getElementById('chart-value-evolution')?.getContext('2d');
        if(ctxValueEvol && data.value_evolution) {
            const evolLabels = data.value_evolution.length > 0 ? data.value_evolution.map(i => i.date) : ['Sin Datos'];
            const evolValues = data.value_evolution.length > 0 ? data.value_evolution.map(i => i.value_change) : [0];
            
            // Calculamos valor acumulativo partiendo de un aproximado
            let acc = 0;
            const accumValues = evolValues.map(v => { acc += parseFloat(v); return acc; });

            chartValueEvol = new Chart(ctxValueEvol, {
                type: 'line',
                data: { labels: evolLabels, datasets: [{ label: 'Variación', data: accumValues, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', borderWidth: 3, tension: 0.4, fill: true }] },
                options: commonOptions
            });
        }

        // 6. Retornos (Líneas / Barras)
        destroyChart(chartReturns);
        const ctxReturns = document.getElementById('chart-returns')?.getContext('2d');
        if(ctxReturns && data.returns_over_time) {
            const rLabels = data.returns_over_time.length > 0 ? data.returns_over_time.map(i => i.return_date) : ['Sin Datos'];
            const rValues = data.returns_over_time.length > 0 ? data.returns_over_time.map(i => i.total_returned) : [0];
            chartReturns = new Chart(ctxReturns, {
                type: 'bar',
                data: { labels: rLabels, datasets: [{ label: 'Devoluciones', data: rValues, backgroundColor: '#ec4899', borderRadius: 4 }] },
                options: commonOptions
            });
        }

        // 7. Top Técnicos
        destroyChart(chartTopTechs);
        const ctxTopTechs = document.getElementById('chart-top-techs')?.getContext('2d');
        if(ctxTopTechs && data.top_technicians) {
            const tLabels = data.top_technicians.length > 0 ? data.top_technicians.map(i => i.technician_name || 'Desconocido') : ['Sin Datos'];
            const tValues = data.top_technicians.length > 0 ? data.top_technicians.map(i => i.total_assigned) : [0];
            chartTopTechs = new Chart(ctxTopTechs, {
                type: 'bar',
                data: { labels: tLabels, datasets: [{ label: 'Material Asignado', data: tValues, backgroundColor: '#8b5cf6', borderRadius: 4, indexAxis: 'y' }] },
                options: { ...commonOptions, indexAxis: 'y' }
            });
        }
    }

    // Funcionalidad Drill-Down Modal
    window.openDrillDown = function(productName) {
        document.getElementById('ddProductName').textContent = productName;
        document.getElementById('ddTableBody').innerHTML = '<tr><td colspan="5" class="text-center">Cargando...</td></tr>';
        document.getElementById('ddProductImage').style.display = 'none';
        
        document.getElementById('drilldownModal').classList.add('active');

        const fd = new FormData();
        fd.append('action', 'get_drilldown_data');
        fd.append('product_name', productName);

        const BASE = document.querySelector('meta[name="base-url"]')?.content || (window.location.pathname.includes('/TURBOSAAS') ? '/TURBOSAAS' : '');
        fetch(BASE + '/ajax/dashboard_inventario.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                if(res.photo) {
                    document.getElementById('ddProductImage').src = BASE + '/' + res.photo;
                    document.getElementById('ddProductImage').style.display = 'block';
                }
                const tbody = document.getElementById('ddTableBody');
                tbody.innerHTML = '';
                if(res.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center" style="color:#94a3b8;">No hay movimientos recientes.</td></tr>';
                    return;
                }
                res.data.forEach(row => {
                    const tr = document.createElement('tr');
                    let actionBadge = `<span class="badge" style="background:#64748b;">${row.action}</span>`;
                    if(row.action === 'assign') actionBadge = `<span class="badge" style="background:#3b82f6;">Asignado</span>`;
                    else if(row.action === 'return') actionBadge = `<span class="badge" style="background:#10b981;">Devuelto</span>`;

                    tr.innerHTML = `
                        <td>${new Date(row.created_at).toLocaleDateString()}</td>
                        <td>${actionBadge}</td>
                        <td>${row.assigned_to_name || '-'}</td>
                        <td><strong>${row.quantity}</strong></td>
                        <td style="color:#64748b; font-size:0.85rem;">${row.notes || '-'}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        });
    }

});
