<!-- Modal de Ubicación Moderno Tipo Uber/InDrive -->
<div class="modal-overlay" id="locationViewerModal" style="z-index: 999999; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(8px); display: none;">
    <div class="modal-content uber-location-modal" style="max-width: 520px; width: 92%; padding: 0; overflow: hidden; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 25px 60px rgba(0,0,0,0.5); animation: scaleUpUber 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <!-- Top Bar Header -->
        <div style="padding: 16px 20px; background: #0f172a; color: white; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; box-shadow: 0 4px 14px rgba(16,185,129,0.35);">
                    <i class="ph-fill ph-navigation-arrow" style="color: white;"></i>
                </div>
                <div>
                    <div style="font-size: 1rem; font-weight: 700; color: #fff; letter-spacing: -0.2px;">Ubicación GPS Compartida</div>
                    <div style="font-size: 0.75rem; color: #94a3b8; display: flex; align-items: center; gap: 4px;">
                        <span style="width: 6px; height: 6px; background: #10b981; border-radius: 50%; display: inline-block;"></span> Mapa Interactivo del Sistema
                    </div>
                </div>
            </div>
            <button onclick="closeLocationViewer()" style="background: rgba(255,255,255,0.1); border: none; color: white; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                <i class="ph-bold ph-x" style="font-size: 1.1rem;"></i>
            </button>
        </div>

        <!-- Embedded Interactive Map Frame -->
        <div style="position: relative; width: 100%; height: 350px; background: #1e293b;">
            <iframe id="locationViewerMapIframe" src="" style="width: 100%; height: 100%; border: none;" allowfullscreen loading="lazy"></iframe>
        </div>

        <!-- Bottom InDrive / Uber Style Card -->
        <div style="padding: 20px; background: #0f172a; color: white;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,0.08);">
                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; font-weight: 700;">Coordenadas Exactas</div>
                    <div id="locationViewerCoordsText" style="font-size: 1.1rem; font-weight: 700; font-family: monospace; color: #10b981; margin-top: 3px; letter-spacing: 0.5px;">-12.046374, -77.042793</div>
                </div>
                <button onclick="copyLocationCoords()" id="btnCopyCoords" style="background: #1e293b; border: 1px solid #334155; color: #e2e8f0; padding: 8px 14px; border-radius: 10px; font-size: 0.82rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                    <i class="ph-bold ph-copy"></i> Copiar
                </button>
            </div>

            <div style="display: flex; gap: 12px;">
                <a id="locationExternalGmapsBtn" href="#" target="_blank" style="flex: 1; text-align: center; text-decoration: none; padding: 12px 16px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; border-radius: 12px; font-weight: 700; font-size: 0.88rem; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 14px rgba(37,99,235,0.35); transition: all 0.2s;">
                    <i class="ph-fill ph-navigation-arrow" style="font-size: 1.1rem;"></i> Navegar en Google Maps
                </a>
                <button onclick="closeLocationViewer()" style="padding: 12px 20px; background: #334155; color: white; border: none; border-radius: 12px; font-weight: 600; font-size: 0.88rem; cursor: pointer; transition: all 0.2s;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes scaleUpUber {
    from { transform: scale(0.92) translateY(20px); opacity: 0; }
    to { transform: scale(1) translateY(0); opacity: 1; }
}
</style>

<script>
let activeLocationCoords = '';

function openLocationViewer(coords) {
    activeLocationCoords = coords;
    const modal = document.getElementById('locationViewerModal');
    const iframe = document.getElementById('locationViewerMapIframe');
    const coordsText = document.getElementById('locationViewerCoordsText');
    const extBtn = document.getElementById('locationExternalGmapsBtn');

    if (modal && iframe) {
        coordsText.textContent = coords;
        extBtn.href = `https://maps.google.com/?q=${coords}`;
        // Google Maps Embed URL
        iframe.src = `https://maps.google.com/maps?q=${coords}&z=17&output=embed`;
        modal.classList.add('active');
        modal.style.display = 'flex';
    }
}

function closeLocationViewer() {
    const modal = document.getElementById('locationViewerModal');
    const iframe = document.getElementById('locationViewerMapIframe');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        if (iframe) iframe.src = '';
    }
}

function copyLocationCoords() {
    if (activeLocationCoords) {
        navigator.clipboard.writeText(activeLocationCoords).then(() => {
            const btn = document.getElementById('btnCopyCoords');
            if (btn) {
                const oldHtml = btn.innerHTML;
                btn.innerHTML = '<i class="ph-bold ph-check" style="color:#10b981;"></i> ¡Copiado!';
                setTimeout(() => { btn.innerHTML = oldHtml; }, 2000);
            }
        });
    }
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('locationViewerModal');
    if (modal && e.target === modal) {
        closeLocationViewer();
    }
});
</script>
