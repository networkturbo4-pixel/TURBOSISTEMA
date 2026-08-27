/**
 * ══════════════════════════════════════════════════════════════════
 * DASHBOARD DE INVENTARIO - FRONTEND CONTROLLER & CHARTS SUITE
 * ══════════════════════════════════════════════════════════════════
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // Chart instances registry
    const charts = {
        status: null,
        lowStock: null,
        mostUsed: null,
        categories: null,
        purchases: null,
        topTechs: null,
        returns: null
    };

    const form = document.getElementById('dashboard-filter-form');
    const BASE = document.querySelector('meta[name="base-url"]')?.content || (window.location.pathname.includes('/TURBOSAAS') ? '/TURBOSAAS' : '');

    // Form submit listener
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            loadDashboardData();
        });
    }

    // Tab switcher
    document.querySelectorAll('.dash-view-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.dash-view-btn').forEach(b => b.classList.remove('active'));
            const clickedBtn = e.currentTarget;
            clickedBtn.classList.add('active');

            const targetTab = clickedBtn.dataset.dtab;
            document.querySelectorAll('.d-tab-content').forEach(pane => {
                pane.style.display = 'none';
                pane.classList.remove('active');
            });

            const activePane = document.getElementById('dtab-' + targetTab);
            if (activePane) {
                activePane.style.display = 'block';
                activePane.classList.add('active');
            }

            // Trigger chart resize if needed
            setTimeout(() => {
                Object.values(charts).forEach(c => {
                    if (c) c.resize();
                });
            }, 100);
        });
    });

    // Quick range filter chips
    window.setDashFilter = function(type) {
        document.querySelectorAll('.dash-chip').forEach(c => c.classList.remove('active'));
        if (event && event.target) event.target.classList.add('active');

        const today = new Date();
        const endStr = today.toISOString().split('T')[0];
        let startStr = endStr;

        if (type === 'today') {
            startStr = endStr;
        } else if (type === 'week') {
            const lastWeek = new Date(today);
            lastWeek.setDate(today.getDate() - 7);
            startStr = lastWeek.toISOString().split('T')[0];
        } else if (type === 'month') {
            startStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-01';
        } else if (type === 'year') {
            startStr = today.getFullYear() + '-01-01';
        }

        const startInput = document.getElementById('filter_start_date');
        const endInput = document.getElementById('filter_end_date');
        if (startInput) startInput.value = startStr;
        if (endInput) endInput.value = endStr;

        loadDashboardData();
    };

    // Currency Formatter
    const formatMoney = (val) => {
        const num = parseFloat(val) || 0;
        return 'S/ ' + num.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    // Dark Mode Detector & Theme Colors
    function isDark() {
        return document.body.classList.contains('dark-theme');
    }

    function getPalette() {
        const dark = isDark();
        return {
            textColor: dark ? '#cbd5e1' : '#475569',
            mutedColor: dark ? '#64748b' : '#94a3b8',
            gridColor: dark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)',
            surfaceBg: dark ? '#18181b' : '#ffffff',
            emerald: '#10b981',
            blue: '#3b82f6',
            amber: '#f59e0b',
            rose: '#ef4444',
            purple: '#8b5cf6',
            cyan: '#06b6d4',
            indigo: '#6366f1'
        };
    }

    function destroyChart(key) {
        if (charts[key]) {
            charts[key].destroy();
            charts[key] = null;
        }
    }

    // Toggle Empty State Helper
    function setEmptyState(canvasId, emptyStateId, isEmpty) {
        const canvas = document.getElementById(canvasId);
        const emptyEl = document.getElementById(emptyStateId);
        if (canvas) canvas.style.display = isEmpty ? 'none' : 'block';
        if (emptyEl) emptyEl.style.display = isEmpty ? 'flex' : 'none';
    }

    // Initial Load
    loadDashboardData();

    // ── Main Data Fetcher ─────────────────────────────────────────
    async function loadDashboardData() {
        try {
            const formData = form ? new FormData(form) : new FormData();
            formData.append('action', 'get_dashboard_data');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            if (csrf) formData.append('csrf_token', csrf);

            const res = await fetch(BASE + '/ajax/dashboard_inventario.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json());

            if (!res.success) {
                if (window.showToast) window.showToast(res.message || 'Error al cargar datos del dashboard', 'error');
                return;
            }

            const d = res.data;

            // 1. Update KPIs
            if (d.metrics) {
                const m = d.metrics;
                const setEl = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val;
                };

                setEl('metricProductos', m.productos_registrados?.current ?? 0);
                setEl('metricDisponible', m.disponibles?.current ?? 0);
                setEl('metricInstalado', m.instalados?.current ?? 0);
                setEl('metricEnPersonal', m.asignado_personal?.current ?? 0);
                setEl('metricLowStock', m.por_agotarse?.current ?? 0);

                const valTotalStr = formatMoney(m.valor_total?.current ?? 0);
                setEl('metricValorTotal', valTotalStr);
                setEl('metricValorTotalFin', valTotalStr);

                const capInmovStr = formatMoney(m.capital_inmovilizado?.current ?? 0);
                setEl('metricCapitalInmovilizado', capInmovStr);
            }

            // 2. Render Charts
            renderStatusDonut(d.status_stats);
            renderLowStockChart(d.lowest_stock);
            renderMostUsedChart(d.most_used, d.most_used_all_time);
            renderCategoryChart(d.category_stats);
            renderPurchasesEvolution(d.purchases_evolution);
            renderTopTechsChart(d.top_technicians);
            renderReturnsChart(d.returns_over_time);

        } catch (err) {
            console.error("Error cargando dashboard:", err);
        }
    }

    // ── 1. Donut Chart de Estados con Centro Prominente ──────────
    function renderStatusDonut(stats) {
        destroyChart('status');
        const ctx = document.getElementById('chart-status');
        if (!ctx) return;

        if (!stats) {
            setEmptyState('chart-status', 'empty-state-status', true);
            return;
        }

        const labels = ['Disponibles en Almacén', 'En Personal / EPP', 'Instalados en Clientes', 'Malogrados / Bajas', 'En Observación'];
        const dataValues = [
            stats.disponible || 0,
            stats.personal_epp || 0,
            stats.instalado || 0,
            stats.malogrado || 0,
            stats.observacion || 0
        ];

        const totalSum = dataValues.reduce((a, b) => a + b, 0);

        if (totalSum === 0) {
            setEmptyState('chart-status', 'empty-state-status', true);
            return;
        }
        setEmptyState('chart-status', 'empty-state-status', false);

        const pal = getPalette();
        const colors = [pal.emerald, pal.amber, pal.blue, pal.rose, pal.cyan];

        // Plugin to write center text
        const centerTextPlugin = {
            id: 'centerTextPlugin',
            afterDraw(chart) {
                const { ctx, chartArea: { top, bottom, left, right, width, height } } = chart;
                ctx.save();
                const centerX = (left + right) / 2;
                const centerY = (top + bottom) / 2;

                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                // Number
                ctx.font = '800 1.65rem Inter, system-ui, sans-serif';
                ctx.fillStyle = pal.textColor;
                ctx.fillText(totalSum.toLocaleString('es-PE'), centerX, centerY - 8);

                // Label
                ctx.font = '600 0.75rem Inter, system-ui, sans-serif';
                ctx.fillStyle = pal.mutedColor;
                ctx.fillText('Unidades', centerX, centerY + 14);

                ctx.restore();
            }
        };

        charts.status = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: dataValues,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: isDark() ? '#18181b' : '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: pal.textColor,
                            boxWidth: 12,
                            boxHeight: 12,
                            padding: 12,
                            font: { family: 'Inter', size: 11, weight: '600' },
                            generateLabels(chart) {
                                const data = chart.data;
                                return data.labels.map((label, i) => {
                                    const val = data.datasets[0].data[i];
                                    const pct = totalSum > 0 ? ((val / totalSum) * 100).toFixed(0) : 0;
                                    return {
                                        text: `${label} (${val} • ${pct}%)`,
                                        fillStyle: colors[i],
                                        strokeStyle: colors[i],
                                        lineWidth: 0,
                                        index: i
                                    };
                                });
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const val = context.raw || 0;
                                const pct = totalSum > 0 ? ((val / totalSum) * 100).toFixed(1) : 0;
                                return ` ${val} unidades (${pct}%)`;
                            }
                        }
                    }
                }
            },
            plugins: [centerTextPlugin]
        });
    }

    // ── 2. Top Alertas de Reposición (Stock Crítico) ─────────────
    function renderLowStockChart(items) {
        destroyChart('lowStock');
        const ctx = document.getElementById('chart-low-stock');
        if (!ctx) return;

        if (!items || items.length === 0) {
            setEmptyState('chart-low-stock', 'empty-state-low-stock', true);
            return;
        }
        setEmptyState('chart-low-stock', 'empty-state-low-stock', false);

        const pal = getPalette();
        const labels = items.map(p => p.display_name || p.product_name);
        const stockData = items.map(p => parseFloat(p.stock) || 0);
        const minStockData = items.map(p => parseFloat(p.stock_minimo) || 0);

        // Bar colors: Red if <= stock_minimo, Amber if close, Indigo if normal
        const barColors = items.map(p => {
            const st = parseFloat(p.stock) || 0;
            const min = parseFloat(p.stock_minimo) || 0;
            if (st <= (parseFloat(p.stock_critico) || 0)) return '#ef4444';
            if (st <= min) return '#f59e0b';
            return '#6366f1';
        });

        charts.lowStock = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Stock Actual',
                        data: stockData,
                        backgroundColor: barColors,
                        borderRadius: 6,
                        barThickness: 16
                    },
                    {
                        label: 'Stock Mínimo Recomendado',
                        data: minStockData,
                        backgroundColor: 'rgba(148, 163, 184, 0.25)',
                        borderColor: pal.mutedColor,
                        borderWidth: 1,
                        borderRadius: 6,
                        barThickness: 8
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: pal.textColor, font: { size: 11, weight: '600' } }
                    }
                },
                scales: {
                    x: {
                        grid: { color: pal.gridColor },
                        ticks: { color: pal.mutedColor, font: { size: 10 } }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: pal.textColor, font: { size: 11, weight: '600' } }
                    }
                }
            }
        });
    }

    // ── 3. Top Rotación y Asignaciones ───────────────────────────
    function renderMostUsedChart(mostUsed, mostUsedAllTime) {
        destroyChart('mostUsed');
        const ctx = document.getElementById('chart-most-used');
        if (!ctx) return;

        const dataToUse = (mostUsed && mostUsed.length > 0) ? mostUsed : (mostUsedAllTime || []);

        if (!dataToUse || dataToUse.length === 0) {
            setEmptyState('chart-most-used', 'empty-state-most-used', true);
            return;
        }
        setEmptyState('chart-most-used', 'empty-state-most-used', false);

        const pal = getPalette();
        const labels = dataToUse.map(p => p.product_name);
        const values = dataToUse.map(p => parseFloat(p.total_used) || 0);

        charts.mostUsed = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Unidades Asignadas / Salidas',
                    data: values,
                    backgroundColor: 'rgba(139, 92, 246, 0.8)',
                    borderColor: '#8b5cf6',
                    borderWidth: 1,
                    borderRadius: 8,
                    barThickness: 24
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        grid: { color: pal.gridColor },
                        ticks: { color: pal.mutedColor, font: { size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: pal.textColor, font: { size: 11, weight: '600' } }
                    }
                }
            }
        });
    }

    // ── 4. Distribución por Categorías ───────────────────────────
    function renderCategoryChart(categories) {
        destroyChart('categories');
        const ctx = document.getElementById('chart-category-stats');
        if (!ctx) return;

        if (!categories || categories.length === 0) {
            setEmptyState('chart-category-stats', 'empty-state-categories', true);
            return;
        }
        setEmptyState('chart-category-stats', 'empty-state-categories', false);

        const pal = getPalette();
        const labels = categories.map(c => c.category_name);
        const stockValues = categories.map(c => parseFloat(c.total_stock) || 0);

        const colors = [
            '#f59e0b', '#3b82f6', '#10b981', '#ec4899', '#8b5cf6', '#06b6d4', '#6366f1', '#64748b'
        ];

        charts.categories = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Stock Total',
                    data: stockValues,
                    backgroundColor: colors.slice(0, labels.length),
                    borderRadius: 8,
                    barThickness: 22
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        grid: { color: pal.gridColor },
                        ticks: { color: pal.mutedColor, font: { size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: pal.textColor, font: { size: 11, weight: '600' } }
                    }
                }
            }
        });
    }

    // ── 5. Inversión en Compras ──────────────────────────────────
    function renderPurchasesEvolution(purchases) {
        destroyChart('purchases');
        const ctx = document.getElementById('chart-purchases-evolution');
        if (!ctx) return;

        if (!purchases || purchases.length === 0) {
            setEmptyState('chart-purchases-evolution', 'empty-state-purchases', true);
            return;
        }
        setEmptyState('chart-purchases-evolution', 'empty-state-purchases', false);

        const pal = getPalette();
        const labels = purchases.map(p => p.purchase_day);
        const spentValues = purchases.map(p => parseFloat(p.total_spent) || 0);

        charts.purchases = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Monto Invertido (S/)',
                    data: spentValues,
                    borderColor: pal.emerald,
                    backgroundColor: 'rgba(16, 185, 129, 0.15)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: pal.emerald
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        grid: { color: pal.gridColor },
                        ticks: {
                            color: pal.mutedColor,
                            callback(val) { return 'S/ ' + val; }
                        }
                    },
                    x: {
                        grid: { color: pal.gridColor },
                        ticks: { color: pal.textColor, font: { size: 10 } }
                    }
                }
            }
        });
    }

    // ── 6. Top Técnicos ──────────────────────────────────────────
    function renderTopTechsChart(techs) {
        destroyChart('topTechs');
        const ctx = document.getElementById('chart-top-techs');
        if (!ctx) return;

        if (!techs || techs.length === 0) {
            setEmptyState('chart-top-techs', 'empty-state-techs', true);
            return;
        }
        setEmptyState('chart-top-techs', 'empty-state-techs', false);

        const pal = getPalette();
        const labels = techs.map(t => t.technician_name || 'Técnico');
        const values = techs.map(t => parseFloat(t.total_assigned) || 0);

        charts.topTechs = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Unidades Asignadas',
                    data: values,
                    backgroundColor: 'rgba(139, 92, 246, 0.85)',
                    borderRadius: 6,
                    barThickness: 20
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { color: pal.gridColor },
                        ticks: { color: pal.mutedColor }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: pal.textColor, font: { weight: '600' } }
                    }
                }
            }
        });
    }

    // ── 7. Devoluciones ──────────────────────────────────────────
    function renderReturnsChart(returns) {
        destroyChart('returns');
        const ctx = document.getElementById('chart-returns');
        if (!ctx) return;

        if (!returns || returns.length === 0) {
            setEmptyState('chart-returns', 'empty-state-returns', true);
            return;
        }
        setEmptyState('chart-returns', 'empty-state-returns', false);

        const pal = getPalette();
        const labels = returns.map(r => r.return_date);
        const values = returns.map(r => parseFloat(r.total_returned) || 0);

        charts.returns = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Unidades Devueltas',
                    data: values,
                    backgroundColor: 'rgba(245, 158, 11, 0.85)',
                    borderRadius: 6,
                    barThickness: 18
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        grid: { color: pal.gridColor },
                        ticks: { color: pal.mutedColor }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: pal.textColor }
                    }
                }
            }
        });
    }

});
