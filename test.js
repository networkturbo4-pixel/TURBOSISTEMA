
        let currentTechTicketId = null;
        let techChatPollInterval = null;
        let techLastMessageId = 0;
        let isTechPolling = false;
        let techSelectedFile = null;

        const escapeHtml = (str) => String(str || '').replace(/[&<>"']/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s]));

        function filterJobs(status, btn) {
            document.querySelectorAll('.feed-filter-pills .pill-btn').forEach(p => p.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const cards = document.querySelectorAll('.job-item-card');
            cards.forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function triggerTechChatFromButton(btn) {
            const ticketId = btn.getAttribute('data-ticket-id');
            const asunto = btn.getAttribute('data-asunto');
            const cliente = btn.getAttribute('data-cliente');
            const status = btn.getAttribute('data-status');
            openTechChat(ticketId, asunto, cliente, status);
        }

        function openTechChat(ticketId, ticketTitle, clientName, currentStatus = 'en_proceso') {
            currentTechTicketId = ticketId;
            techLastMessageId = 0;
            document.getElementById('techChatTicketTitle').textContent = `#${String(ticketId).padStart(4, '0')} - ${ticketTitle}`;
            document.getElementById('techChatClientName').innerHTML = `<i class="ph-fill ph-user-circle"></i> ${escapeHtml(clientName)}`;
            document.getElementById('techTicketStatusSelect').value = currentStatus;
            document.getElementById('techChatMessages').innerHTML = '<div style="text-align:center; padding:30px; color:#94a3b8;"><i class="ph ph-spinner spinner" style="font-size:1.5rem;"></i><br><span style="font-size:0.85rem; margin-top:6px; display:inline-block;">Cargando conversación...</span></div>';
            
            const modal = document.getElementById('techChatModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('active');
            }
            
            history.pushState({modal: 'techChatModal'}, '', '#chat');
            
            loadTechChatMessages();
            if (techChatPollInterval) clearInterval(techChatPollInterval);
            techChatPollInterval = setInterval(loadTechChatMessages, 1200);
        }

        function closeTechChat(fromHistory = false) {
            const modal = document.getElementById('techChatModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }
            if (techChatPollInterval) {
                clearInterval(techChatPollInterval);
                techChatPollInterval = null;
            }
            currentTechTicketId = null;
            if (!fromHistory && window.location.hash === '#chat') {
                history.back();
            }
        }

        async function loadTechChatMessages() {
            if (!currentTechTicketId || isTechPolling) return;
            isTechPolling = true;

            try {
                const fd = new FormData();
                fd.append('action', 'get_messages');
                fd.append('ticket_id', currentTechTicketId);
                fd.append('last_id', techLastMessageId);

                fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
                
                if (res.success) {
                    const container = document.getElementById('techChatMessages');
                    
                    if (techLastMessageId === 0 && (!res.data || res.data.length === 0)) {
                        container.innerHTML = `
                            <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                                <i class="ph-fill ph-chat-circle-dots" style="font-size:2.5rem; color:#3b82f6; margin-bottom:8px;"></i>
                                <div style="font-weight:700; color:#fff;">Inicio de Conversación</div>
                                <div style="font-size:0.8rem; margin-top:4px;">No hay mensajes previos en este ticket. ¡Escribe un mensaje para iniciar!</div>
                            </div>
                        `;
                    } else if (res.data && res.data.length > 0) {
                        const isFirstLoad = (techLastMessageId === 0);
                        if (isFirstLoad) container.innerHTML = '';

                        let htmlBuffer = '';
                        res.data.forEach(msg => {
                            if (msg.is_system_message == 1) {
                                htmlBuffer += `<div style="text-align:center; margin:8px 0; font-size:0.75rem; color:#94a3b8; background:rgba(255,255,255,0.05); padding:4px 12px; border-radius:12px; align-self:center;">${escapeHtml(msg.message)}</div>`;
                                techLastMessageId = msg.id;
                            } else {
                                const isMe = msg.user_id !== null;
                                const userName = isMe ? 'Tú (Técnico)' : (msg.user_name || 'Cliente');
                                
                                let msgContent = escapeHtml(msg.message).replace(/\n/g, '<br>');
                                
                                if (msgContent.startsWith('[LOCATION:') && msgContent.endsWith(']')) {
                                    const coords = msgContent.replace('[LOCATION:', '').replace(']', '');
                                    msgContent = `
                                        <div onclick="openLocationViewer('${coords}')" class="loc-card" style="cursor: pointer; background: rgba(15, 23, 42, 0.6); padding: 10px; border-radius: 12px; display: flex; align-items: center; gap: 10px; border: 1px solid rgba(255,255,255,0.1);">
                                            <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 10px; border-radius: 10px; display: flex; align-items: center; justify-content: center;"><i class="ph-fill ph-navigation-arrow" style="font-size: 1.3rem;"></i></div>
                                            <div>
                                                <div style="font-weight: 700; font-size: 0.88rem; color: #fff;">Ubicación compartida</div>
                                                <div style="font-size: 0.75rem; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 4px; margin-top: 2px;"><i class="ph-fill ph-map-pin"></i> Ver en Mapa App</div>
                                            </div>
                                        </div>
                                    `;
                                }

                                let attHtml = '';
                                if (msg.attachments && msg.attachments.length > 0) {
                                    msg.attachments.forEach(att => {
                                        let url = att.file_path;
                                        if (!url.startsWith('http://') && !url.startsWith('https://')) {
                                            url = `<?php echo BASE_URL; ?>/` + url;
                                        }
                                        const ext = att.file_name.split('.').pop().toLowerCase();
                                        const isVideo = ['mp4', 'mov', 'avi', 'mkv', 'webm'].includes(ext) || (ext === 'webm' && !att.file_name.includes('Nota de Voz'));
                                        const isAudio = ['mp3', 'ogg', 'wav', 'm4a'].includes(ext) || (ext === 'webm' && att.file_name.includes('Nota de Voz'));
                                        
                                        if (isVideo) {
                                            const isDriveUrl = url.includes('drive.google.com');
                                            if (isDriveUrl) {
                                                let embedUrl = url;
                                                // Convertir cualquier URL de Drive a formato /preview
                                                const fileIdMatch = url.match(/\/d\/([a-zA-Z0-9_-]+)/);
                                                const ucIdMatch = url.match(/[?&]id=([a-zA-Z0-9_-]+)/);
                                                const driveFileId = fileIdMatch ? fileIdMatch[1] : (ucIdMatch ? ucIdMatch[1] : null);
                                                
                                                if (driveFileId) {
                                                    embedUrl = `https://drive.google.com/file/d/${driveFileId}/preview`;
                                                } else if (url.includes('/view')) {
                                                    embedUrl = url.replace(/\/view.*$/, '/preview');
                                                }
                                                attHtml += `<div style="margin-top: 6px; border-radius: 10px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1);"><iframe src="${embedUrl}" width="100%" height="220" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="border:0;"></iframe></div>`;
                                            } else {
                                                attHtml += `<video controls playsinline preload="metadata" style="max-width: 100%; border-radius: 10px; margin-top: 6px; border: 1px solid rgba(255,255,255,0.1); background: #000;">
                                                    <source src="${url}" type="video/${ext === 'webm' ? 'webm' : ext === 'mov' ? 'quicktime' : 'mp4'}">Tu navegador no soporta video.
                                                </video>`;
                                            }
                                        } else if (isAudio) {
                                            attHtml += `<audio controls src="${url}" style="max-width: 100%; margin-top: 5px; outline: none; height: 35px;"></audio>`;
                                        } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                                            attHtml += `<img src="${url}" referrerpolicy="no-referrer" onclick="openLightbox('${url}')" style="cursor: pointer; max-width: 100%; border-radius: 10px; margin-top: 6px; border: 1px solid rgba(255,255,255,0.1);">`;
                                        } else {
                                            attHtml += `<div style="margin-top: 6px;"><a href="${url}" target="_blank" style="color: inherit; text-decoration: underline; font-weight: 600;"><i class="ph-fill ph-file"></i> ${escapeHtml(att.file_name)}</a></div>`;
                                        }
                                    });
                                }
                                
                                const alignSelf = isMe ? 'flex-end' : 'flex-start';
                                const bgMsg = isMe ? 'linear-gradient(135deg, #1e3a8a, #1d4ed8)' : 'rgba(30, 41, 59, 0.9)';

                                htmlBuffer += `
                                    <div style="align-self: ${alignSelf}; max-width: 82%; background: ${bgMsg}; padding: 10px 14px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); font-size: 0.88rem;">
                                        <div style="font-size: 0.72rem; font-weight: 700; color: ${isMe ? '#93c5fd' : '#10b981'}; margin-bottom: 3px;">${userName}</div>
                                        <div>${msgContent}</div>
                                        ${attHtml}
                                    </div>
                                `;
                                techLastMessageId = msg.id;
                            }
                        });
                        
                        if (htmlBuffer !== '') {
                            container.insertAdjacentHTML('beforeend', htmlBuffer);
                            container.scrollTop = container.scrollHeight;
                        }
                    }
                }
            } catch(e) {
                console.error("Error al cargar mensajes del chat:", e);
            } finally {
                isTechPolling = false;
            }
        }

        async function updateTechTicketStatusFromChat(newStatus) {
            if (!currentTechTicketId) return;
            const fd = new FormData();
            fd.append('action', 'update_status');
            fd.append('ticket_id', currentTechTicketId);
            fd.append('status', newStatus);

            try {
            fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
                if (res.success) {
                    loadTechChatMessages();
                }
            } catch(e) {}
        }

        const toggleTechActionMenu = () => {
            const menu = document.getElementById('techChatActionMenu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        };

        const toggleEmojiPicker = () => {
            const picker = document.getElementById('emojiPicker');
            picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
        };

        // Manejo del evento del emoji picker
        document.querySelector('emoji-picker').addEventListener('emoji-click', event => {
            insertEmoji(event.detail.unicode);
        });

        const insertEmoji = (emoji) => {
            const input = document.getElementById('techMessageInput');
            input.value += emoji;
            input.focus();
            input.style.height = ''; 
            input.style.height = input.scrollHeight + 'px';
            updateTechMainButton();
            toggleEmojiPicker();
        };

        const updateTechMainButton = () => {
            const text = document.getElementById('techMessageInput').value.trim();
            const btnIcon = document.getElementById('btnTechSendIcon');
            if (text || techSelectedFile) {
                btnIcon.className = 'ph-fill ph-paper-plane-right';
            } else {
                btnIcon.className = 'ph-fill ph-microphone';
            }
        };

        const clearTechFileSelection = () => {
            techSelectedFile = null;
            document.getElementById('techFilePreviewContainer').style.display = 'none';
            updateTechMainButton();
        };

        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                openMediaPreview(input.files[0]);
                input.value = ''; // Reset for future selections
            }
        }

        // Ya no enviamos directo, pasamos por la vista previa
        function sendCapturedFileDirectly(file) {
            if (!file) return;
            openMediaPreview(file);
        }

        const openGalleryInput = () => {
            document.getElementById('techFileInput').click();
        };

        const sendTechLocation = () => {
            if (!navigator.geolocation) {
                alert('Tu dispositivo no soporta geolocalización');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const fd = new FormData();
                    fd.append('action', 'send_message');
                    fd.append('ticket_id', currentTechTicketId);
                    fd.append('message', `[LOCATION:${lat},${lng}]`);
                    try {
                        fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                        const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
                        if(res.success) loadTechChatMessages();
                    } catch(e) {}
                },
                (error) => {
                    alert('No se pudo obtener la ubicación.');
                }
            );
        };

        const handleTechMainAction = () => {
            const btnIcon = document.getElementById('btnTechSendIcon').className;
            if (btnIcon.includes('ph-microphone')) {
                if (isTechRecording) {
                    techMediaRecorder.stop();
                } else {
                    startTechRecording();
                }
            } else {
                sendTechTextMessage();
            }
        };

        const showTechUploadBanner = (filename) => {
            const banner = document.getElementById('techChatUploadingBanner');
            const filenameEl = document.getElementById('techChatUploadFilename');
            const fillEl = document.getElementById('techChatUploadProgressFill');
            const percentEl = document.getElementById('techChatUploadPercentText');
            if (banner) {
                filenameEl.textContent = filename || 'Archivo multimedia';
                fillEl.style.width = '10%';
                if (percentEl) percentEl.textContent = '10%';
                banner.style.display = 'block';
            }
        };

        const updateTechUploadProgress = (percent) => {
            const fillEl = document.getElementById('techChatUploadProgressFill');
            const percentEl = document.getElementById('techChatUploadPercentText');
            const p = Math.min(100, Math.max(10, Math.round(percent)));
            if (fillEl) fillEl.style.width = p + '%';
            if (percentEl) percentEl.textContent = p + '%';
        };

        const hideTechUploadBanner = () => {
            const banner = document.getElementById('techChatUploadingBanner');
            if (banner) banner.style.display = 'none';
        };

        const sendTechChatAjaxWithProgress = (formData, filename = null) => {
            return new Promise((resolve, reject) => {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken && !formData.has('csrf_token')) {
                    formData.append('csrf_token', csrfToken);
                }

                if (filename) showTechUploadBanner(filename);
                
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo BASE_URL; ?>/ajax/soporte.php', true);
                if (csrfToken) {
                    xhr.setRequestHeader('X-CSRF-Token', csrfToken);
                }

                if (filename && xhr.upload) {
                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            const percent = (e.loaded / e.total) * 100;
                            updateTechUploadProgress(percent);
                        }
                    };
                }

                xhr.onload = () => {
                    hideTechUploadBanner();
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            resolve(JSON.parse(xhr.responseText));
                        } catch(e) {
                            reject(e);
                        }
                    } else {
                        reject(new Error(xhr.statusText));
                    }
                };
                xhr.onerror = () => {
                    hideTechUploadBanner();
                    reject(new Error("Network Error"));
                };
                xhr.send(formData);
            });
        };

        const compressImage = (file, maxWidth = 1600, maxHeight = 1600, quality = 0.8) => {
            return new Promise((resolve) => {
                if (!file.type.startsWith('image/') || file.type === 'image/gif') {
                    resolve(file);
                    return;
                }
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = (event) => {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = () => {
                        let width = img.width;
                        let height = img.height;
                        if (width > maxWidth || height > maxHeight) {
                            if (width > height) {
                                height = Math.round((height *= maxWidth / width));
                                width = maxWidth;
                            } else {
                                width = Math.round((width *= maxHeight / height));
                                height = maxHeight;
                            }
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        canvas.toBlob((blob) => {
                            if (blob) {
                                const newFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", { type: 'image/jpeg', lastModified: Date.now() });
                                resolve(newFile);
                            } else {
                                resolve(file);
                            }
                        }, 'image/jpeg', quality);
                    };
                    img.onerror = () => resolve(file);
                };
                reader.onerror = () => resolve(file);
            });
        };

        const sendTechTextMessage = async () => {
            const input = document.getElementById('techMessageInput');
            const text = input.value.trim();
            let fileToSend = techSelectedFile;
            if (!text && !fileToSend || !currentTechTicketId) return;

            input.value = '';
            input.style.height = '';
            if (fileToSend) clearTechFileSelection();

            if (fileToSend && fileToSend.type.startsWith('image/')) {
                fileToSend = await compressImage(fileToSend);
            }

            const fd = new FormData();
            fd.append('action', 'send_message');
            fd.append('ticket_id', currentTechTicketId);
            fd.append('message', text);
            if (fileToSend) {
                fd.append('attachment', fileToSend);
            }

            try {
                const res = await sendTechChatAjaxWithProgress(fd, fileToSend ? fileToSend.name : null);
                if (res.success) {
                    loadTechChatMessages();
                    updateTechMainButton();
                } else {
                    alert(res.error || res.message || 'Error al enviar');
                }
            } catch(e) {
                alert('Error de conexión al enviar.');
            }
        };

        // Audio Recording Logic
        let isTechRecording = false;
        let techMediaRecorder = null;
        let techAudioChunks = [];
        let techRecordingTimerInterval = null;
        let techRecordingSeconds = 0;

        const startTechRecording = async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                techMediaRecorder = new MediaRecorder(stream);
                techAudioChunks = [];
                
                techMediaRecorder.addEventListener("dataavailable", event => {
                    techAudioChunks.push(event.data);
                });
                
                techMediaRecorder.addEventListener("stop", () => {
                    if (isTechRecording) {
                        const audioBlob = new Blob(techAudioChunks, { type: 'audio/webm' });
                        sendTechAudioMessage(audioBlob);
                    }
                    isTechRecording = false;
                    stream.getTracks().forEach(track => track.stop());
                    
                    document.getElementById('techAudioRecordingUi').style.display = 'none';
                    document.getElementById('techMessageInput').style.display = 'block';
                    document.querySelector('button[onclick="toggleTechActionMenu()"]').style.display = 'block';
                    updateTechMainButton();
                    clearInterval(techRecordingTimerInterval);
                });

                techMediaRecorder.start();
                isTechRecording = true;
                
                document.getElementById('techMessageInput').style.display = 'none';
                document.querySelector('button[onclick="toggleTechActionMenu()"]').style.display = 'none';
                document.getElementById('techAudioRecordingUi').style.display = 'flex';
                document.getElementById('btnTechSendIcon').className = 'ph-fill ph-paper-plane-right';
                
                techRecordingSeconds = 0;
                document.getElementById('techRecordingTimer').textContent = '00:00';
                techRecordingTimerInterval = setInterval(() => {
                    techRecordingSeconds++;
                    const m = String(Math.floor(techRecordingSeconds / 60)).padStart(2, '0');
                    const s = String(techRecordingSeconds % 60).padStart(2, '0');
                    document.getElementById('techRecordingTimer').textContent = `${m}:${s}`;
                }, 1000);

            } catch (err) {
                alert('No se pudo acceder al micrófono. Por favor, revisa los permisos del navegador.');
            }
        };

        const cancelTechRecording = () => {
            if (isTechRecording && techMediaRecorder) {
                isTechRecording = false; // flag to not send
                techMediaRecorder.stop();
            }
        };

        const sendTechAudioMessage = async (audioBlob) => {
            const btnSend = document.getElementById('btnTechSend');
            if (btnSend) btnSend.disabled = true;

            const tempId = 'opt_audio_' + Date.now();
            const container = document.getElementById('techChatMessages');
            container.innerHTML += `
                <div style="align-self: flex-end; background: #1e293b; color: white; padding: 12px 16px; border-radius: 16px 16px 0 16px; max-width: 80%; border: 1px solid rgba(255,255,255,0.08); display: flex; flex-direction: column; gap: 8px;">
                    <div style="font-size:0.85rem; font-weight:600;"><i class="ph-fill ph-microphone"></i> Grabación de audio...</div>
                    <div style="font-size: 0.75rem; color: #3b82f6;"><i class="ph ph-spinner spinner"></i> Subiendo a Google Drive...</div>
                </div>`;
            container.scrollTop = container.scrollHeight;

            const fd = new FormData();
            fd.append('action', 'send_message');
            fd.append('ticket_id', currentTechTicketId);
            fd.append('message', '');
            fd.append('attachment', audioBlob, 'audio_record.webm');
            fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            try {
                const res = await sendTechChatAjaxWithProgress(fd, 'Nota de Voz.webm');
                if (res.success) {
                    loadTechChatMessages();
                } else {
                    alert(res.error || 'Error al enviar audio');
                }
            } catch(e) {
                alert('Error de conexión');
            } finally {
                if (btnSend) btnSend.disabled = false;
                const optEl = document.getElementById(tempId);
                if (optEl) optEl.remove();
            }
        };
    

        function openTechAppModule(url, title = 'Módulo') {
            document.getElementById('techAppModalTitle').innerText = title;
            document.getElementById('techAppModalIframe').src = url + (url.includes('?') ? '&' : '?') + 'embedded=1';
            const modal = document.getElementById('techAppViewModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('active');
            }
            history.pushState({modal: 'techAppViewModal'}, '', '#modulo');
        }

        function closeTechAppModal(fromHistory = false) {
            const modal = document.getElementById('techAppViewModal');
            const iframe = document.getElementById('techAppModalIframe');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
                if (iframe) iframe.src = '';
            }
            if (!fromHistory && window.location.hash === '#modulo') {
                history.back();
            }
        }

        // Manejar el botón "Atrás" del navegador/celular
        window.addEventListener('popstate', function(event) {
            if (document.getElementById('techChatModal') && document.getElementById('techChatModal').style.display === 'flex') {
                closeTechChat(true);
            }
            if (document.getElementById('techAppViewModal') && document.getElementById('techAppViewModal').style.display === 'flex') {
                closeTechAppModal(true);
            }
            if (document.getElementById('techLightboxModal') && document.getElementById('techLightboxModal').style.display === 'flex') {
                closeLightbox();
            }
        });
    

        function openLightbox(url) {
            document.getElementById('techLightboxImage').src = url;
            document.getElementById('techLightboxModal').style.display = 'flex';
        }
        function closeLightbox() {
            document.getElementById('techLightboxModal').style.display = 'none';
            document.getElementById('techLightboxImage').src = '';
        }
    