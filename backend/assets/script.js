// Analytics Dashboard JavaScript

class SessionPlayer {
    constructor() {
        this.isPlaying = false;
        this.currentFrame = 0;
        this.frames = [];
        this.frameRate = 2; // fps
        this.playInterval = null;
        this.speeds = [0.5, 1, 1.5, 2, 4];
        this.currentSpeedIndex = 1;
        this.metadata = null;
        
        this.initializeElements();
    }
    
    initializeElements() {
        this.modal = document.getElementById('sessionModal');
        this.frameImg = document.getElementById('sessionFrame');
        this.playPauseBtn = document.getElementById('playPauseBtn');
        this.progressBar = document.getElementById('progressBar');
        this.timeDisplay = document.getElementById('timeDisplay');
        this.speedDisplay = document.getElementById('speedDisplay');
        this.modalTitle = document.getElementById('modalTitle');
        this.sessionMetadata = document.getElementById('sessionMetadata');
    }
    
    async loadSession(userId, date) {
        try {
            this.showLoading();
            
            // Buscar lista de screenshots da sessão
            const response = await fetch(`session-data.php?user=${encodeURIComponent(userId)}&date=${encodeURIComponent(date)}`);
            const sessionData = await response.json();
            
            if (!sessionData.success) {
                throw new Error(sessionData.error || 'Erro ao carregar sessão');
            }
            
            this.frames = sessionData.frames;
            this.metadata = sessionData.metadata;
            this.currentFrame = 0;
            
            if (this.frames.length === 0) {
                throw new Error('Nenhum frame encontrado para esta sessão');
            }
            
            // Configurar player
            this.setupPlayer(userId, date);
            this.loadFrame(0);
            this.updateMetadataPanel();
            this.hideLoading();
            
        } catch (error) {
            console.error('Erro ao carregar sessão:', error);
            alert('Erro ao carregar sessão: ' + error.message);
            this.closeModal();
        }
    }
    
    setupPlayer(userId, date) {
        this.modalTitle.textContent = `Sessão ${userId} - ${this.formatDate(date)}`;
        this.progressBar.max = this.frames.length - 1;
        this.progressBar.value = 0;
        this.updateTimeDisplay();
        this.speedDisplay.textContent = `${this.speeds[this.currentSpeedIndex]}x`;
    }
    
    loadFrame(frameIndex) {
        if (frameIndex >= 0 && frameIndex < this.frames.length) {
            this.currentFrame = frameIndex;
            this.frameImg.src = this.frames[frameIndex].url;
            this.progressBar.value = frameIndex;
            this.updateTimeDisplay();
        }
    }
    
    updateMetadataPanel() {
        if (!this.metadata) return;
        
        const metadataHtml = `
            <div class="metadata-item">
                <strong>Usuário:</strong> ${this.metadata.userId || 'N/A'}
            </div>
            <div class="metadata-item">
                <strong>Data/Hora:</strong> ${this.formatDateTime(this.metadata.timestamp)}
            </div>
            <div class="metadata-item">
                <strong>Total de Frames:</strong> ${this.frames.length}
            </div>
            <div class="metadata-item">
                <strong>Duração:</strong> ${this.formatDuration(this.frames.length / this.frameRate)}
            </div>
            ${this.metadata.userData ? this.formatUserData(this.metadata.userData) : ''}
            ${this.metadata.geo ? this.formatGeoData(this.metadata.geo) : ''}
        `;
        
        this.sessionMetadata.innerHTML = metadataHtml;
    }
    
    formatUserData(userData) {
        let html = '<div class="metadata-section"><strong>Dados do Usuário:</strong><ul>';
        for (const [key, value] of Object.entries(userData)) {
            html += `<li><strong>${key}:</strong> ${value}</li>`;
        }
        html += '</ul></div>';
        return html;
    }
    
    formatGeoData(geoData) {
        const relevantFields = ['country', 'region', 'city', 'ip'];
        let html = '<div class="metadata-section"><strong>Localização:</strong><ul>';
        
        for (const field of relevantFields) {
            if (geoData[field]) {
                html += `<li><strong>${this.capitalizeFirst(field)}:</strong> ${geoData[field]}</li>`;
            }
        }
        
        html += '</ul></div>';
        return html;
    }
    
    play() {
        if (this.isPlaying) return;
        
        this.isPlaying = true;
        this.playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
        
        const frameDelay = 1000 / (this.frameRate * this.speeds[this.currentSpeedIndex]);
        
        this.playInterval = setInterval(() => {
            if (this.currentFrame >= this.frames.length - 1) {
                this.pause();
                return;
            }
            
            this.loadFrame(this.currentFrame + 1);
        }, frameDelay);
    }
    
    pause() {
        this.isPlaying = false;
        this.playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
        
        if (this.playInterval) {
            clearInterval(this.playInterval);
            this.playInterval = null;
        }
    }
    
    togglePlayPause() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }
    
    seekTo(frameIndex) {
        const wasPlaying = this.isPlaying;
        this.pause();
        this.loadFrame(parseInt(frameIndex));
        
        if (wasPlaying) {
            this.play();
        }
    }
    
    changeSpeed() {
        this.currentSpeedIndex = (this.currentSpeedIndex + 1) % this.speeds.length;
        this.speedDisplay.textContent = `${this.speeds[this.currentSpeedIndex]}x`;
        
        // Reiniciar reprodução se estiver tocando
        if (this.isPlaying) {
            this.pause();
            this.play();
        }
    }
    
    updateTimeDisplay() {
        const current = this.formatDuration(this.currentFrame / this.frameRate);
        const total = this.formatDuration(this.frames.length / this.frameRate);
        this.timeDisplay.textContent = `${current} / ${total}`;
    }
    
    formatDuration(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }
    
    formatDateTime(timestamp) {
        if (!timestamp) return 'N/A';
        const date = new Date(timestamp * 1000);
        return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR');
    }
    
    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    showModal() {
        this.modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    closeModal() {
        this.modal.classList.remove('active');
        document.body.style.overflow = '';
        this.pause();
        this.frames = [];
        this.currentFrame = 0;
        this.metadata = null;
    }
    
    showLoading() {
        this.frameImg.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8ZGVmcz4KICA8L2RlZnM+CiAgPHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iIzMzMzMzMyIvPgogIDx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiNmZmZmZmYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5DYXJyZWdhbmRvLi4uPC90ZXh0Pgo8L3N2Zz4K';
    }
    
    hideLoading() {
        // A imagem será substituída quando o primeiro frame carregar
    }
}

// Instanciar o player
const sessionPlayer = new SessionPlayer();

// Funções globais para interação com o HTML
function playSession(userId, date) {
    sessionPlayer.showModal();
    sessionPlayer.loadSession(userId, date);
}

function closeSessionModal() {
    sessionPlayer.closeModal();
}

function togglePlayPause() {
    sessionPlayer.togglePlayPause();
}

function seekTo(value) {
    sessionPlayer.seekTo(value);
}

function changeSpeed() {
    sessionPlayer.changeSpeed();
}

// Função para deletar dados do usuário
function deleteUserData(userId) {
    const confirmMessage = `⚠️ ATENÇÃO!\n\nVocê está prestes a deletar TODOS os dados do usuário "${userId}":\n\n• Screenshots de todas as sessões\n• Todos os eventos registrados\n• Informações pessoais\n• Dados de localização\n\nEsta ação é IRREVERSÍVEL!\n\nDigite "DELETAR" para confirmar:`;
    
    const confirmation = prompt(confirmMessage);
    
    if (confirmation !== 'DELETAR') {
        if (confirmation !== null) {
            showToast('Operação cancelada. Digite "DELETAR" para confirmar.', 'error');
        }
        return;
    }
    
    // Mostrar loading
    const deleteButton = document.querySelector(`button[onclick="deleteUserData('${userId}')"]`);
    const originalContent = deleteButton.innerHTML;
    deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deletando...';
    deleteButton.disabled = true;
    
    // Fazer requisição de delete
    fetch(`/delete-user?userId=${encodeURIComponent(userId)}`, {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`Dados do usuário "${userId}" deletados com sucesso!`, 'success');
            
            // Redirecionar para a página principal após 2 segundos
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 2000);
        } else {
            throw new Error(data.error || 'Erro desconhecido');
        }
    })
    .catch(error => {
        console.error('Erro ao deletar usuário:', error);
        showToast(`Erro ao deletar usuário: ${error.message}`, 'error');
        
        // Restaurar botão
        deleteButton.innerHTML = originalContent;
        deleteButton.disabled = false;
    });
}

// Auto-refresh das estatísticas a cada 30 segundos
function autoRefresh() {
    // Verificar se não estamos no player
    if (!sessionPlayer.modal.classList.contains('active')) {
        location.reload();
    }
}

// Configurar auto-refresh
// setInterval(autoRefresh, 30000);

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && sessionPlayer.modal.classList.contains('active')) {
        closeSessionModal();
    }
});

// Fechar modal clicando fora dele
sessionPlayer.modal.addEventListener('click', function(e) {
    if (e.target === sessionPlayer.modal) {
        closeSessionModal();
    }
});

// Adicionar estilos CSS para os metadados via JavaScript
const metadataStyles = `
<style>
.metadata-item {
    margin-bottom: 0.75rem;
    padding: 0.5rem;
    background: white;
    border-radius: 6px;
    border-left: 3px solid var(--primary-color);
}

.metadata-section {
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: white;
    border-radius: 8px;
}

.metadata-section ul {
    margin-top: 0.5rem;
    padding-left: 1rem;
}

.metadata-section li {
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.session-thumbnail img {
    transition: transform 0.3s ease;
}

.session-card:hover .session-thumbnail img {
    transform: scale(1.05);
}

/* Notificação toast */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--success-color);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    box-shadow: var(--shadow-lg);
    z-index: 1001;
    transform: translateX(400px);
    transition: transform 0.3s ease;
}

.toast.show {
    transform: translateX(0);
}

.toast.error {
    background: var(--error-color);
}
</style>
`;

// Adicionar estilos à página
document.head.insertAdjacentHTML('beforeend', metadataStyles);

// Função para mostrar notificações
function showToast(message, type = 'success') {
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Adicionar loading states aos cards de sessão
document.addEventListener('DOMContentLoaded', function() {
    const sessionCards = document.querySelectorAll('.session-card');
    
    sessionCards.forEach(card => {
        card.addEventListener('click', function() {
            const playBtn = card.querySelector('.play-btn');
            if (playBtn) {
                playBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                setTimeout(() => {
                    playBtn.innerHTML = '<i class="fas fa-play"></i>';
                }, 1000);
            }
        });
    });
});

console.log('Analytics Dashboard loaded successfully! 🚀'); 