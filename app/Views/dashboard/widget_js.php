<script>
document.addEventListener('DOMContentLoaded', function() {
    const widget = {
        elements: {
            badge: document.getElementById('wa-status-badge'),
            connected: document.getElementById('wa-connected'),
            disconnected: document.getElementById('wa-disconnected'),
            connecting: document.getElementById('wa-connecting'),
            phone: document.getElementById('wa-phone'),
            qrImg: document.getElementById('wa-qr-img'),
            qrLoading: document.getElementById('wa-qr-loading')
        },
        
        init: function() {
            this.poll();
            // Polling cada 5 segundos
            setInterval(() => this.poll(), 5000);
        },
        
        poll: function() {
            fetch('/dashboard/whatsapp/status')
                .then(response => {
                    if (!response.ok) {
                        // Tentar ler o corpo do erro se for JSON
                        return response.text().then(text => {
                            let errMsg = `HTTP ${response.status}`;
                            try {
                                const errJson = JSON.parse(text);
                                if (errJson.message) errMsg += `: ${errJson.message}`;
                            } catch (e) {
                                // Se não for JSON, usa o texto puro truncado
                                if (text.length > 0) errMsg += `: ${text.substring(0, 50)}`;
                            }
                            throw new Error(errMsg);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    // console.log('WA Status:', data); // Debug
                    this.updateUI(data);
                })
                .catch(err => {
                    console.error('Error polling WhatsApp status:', err);
                    this.elements.badge.className = 'badge rounded-pill bg-danger';
                    // Mostrar mensagem de erro específica
                    this.elements.badge.textContent = err.message.replace('Error: ', '').substring(0, 20);
                    // Tooltip ou title para ver erro completo
                    this.elements.badge.title = err.message;
                });
        },
        
        updateUI: function(data) {
            // Ocultar todos los estados primero
            this.elements.connected.classList.add('d-none');
            this.elements.disconnected.classList.add('d-none');
            this.elements.connecting.classList.add('d-none');
            
            switch(data.status) {
                case 'connected':
                    this.elements.connected.classList.remove('d-none');
                    this.elements.badge.className = 'badge rounded-pill bg-success';
                    this.elements.badge.textContent = 'Conectado';
                    this.elements.phone.textContent = this.formatPhone(data.phone);
                    break;
                    
                case 'qr_ready':
                    this.elements.disconnected.classList.remove('d-none');
                    this.elements.badge.className = 'badge rounded-pill bg-danger';
                    this.elements.badge.textContent = 'Desconectado';
                    
                    if (data.qr) {
                        this.elements.qrLoading.classList.add('d-none');
                        this.elements.qrImg.classList.remove('d-none');
                        // Verificar si es base64 puro o data URI
                        if (data.qr.startsWith('data:')) {
                            this.elements.qrImg.src = data.qr;
                        } else {
                            this.elements.qrImg.src = 'data:image/png;base64,' + data.qr;
                        }
                    } else {
                        this.elements.qrLoading.classList.remove('d-none');
                        this.elements.qrImg.classList.add('d-none');
                    }
                    break;
                    
                case 'connecting':
                    this.elements.connecting.classList.remove('d-none');
                    this.elements.badge.className = 'badge rounded-pill bg-warning text-dark';
                    this.elements.badge.textContent = 'Conectando...';
                    break;
                    
                default:
                    // Caso error ou status desconhecido
                    this.elements.badge.className = 'badge rounded-pill bg-secondary';
                    this.elements.badge.textContent = 'Sin Conexión API';
            }
        },
        
        saveConfig: function() {
            const formData = new FormData(this.elements.configForm);
            
            fetch('/dashboard/whatsapp/config', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Configuración guardada correctamente');
                    // Opcional: cerrar panel
                    const collapse = bootstrap.Collapse.getInstance(document.getElementById('wa-config-panel'));
                    if (collapse) collapse.hide();
                } else {
                    alert('Error: ' + (data.error || 'Desconocido'));
                }
            })
            .catch(err => alert('Error de red al guardar configuración'));
        },
        
        formatPhone: function(phone) {
            if (!phone) return '--';
            // Formato +56 9 XXXX XXXX
            return '+' + phone.replace(/(\d{2})(\d{1})(\d{4})(\d{4})/, '$1 $2 $3 $4');
        }
    };
    
    widget.init();
});
</script>
