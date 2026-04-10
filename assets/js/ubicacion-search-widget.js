class UbicacionSearchWidget {
    constructor(inputElement, options = {}) {
        if (!inputElement) {
            console.error('UbicacionSearchWidget: inputElement es requerido');
            return;
        }
        
        console.log('🎯 Inicializando widget para:', inputElement.id);
        
        this.inputElement = inputElement;
        this.options = {
            apiUrl: options.apiUrl || '<?= APP_URL ?>/modules/ubicaciones/ubicaciones_api.php',
            latInputId: options.latInputId || null,
            lngInputId: options.lngInputId || null,
            onSelect: options.onSelect || null,
            minChars: options.minChars || 3,
            debounceTime: options.debounceTime || 300,
            placeholder: options.placeholder || 'Buscar ubicación...',
            showPreview: options.showPreview !== false,
            previewContainerId: options.previewContainerId || null,
            autoSave: options.autoSave !== false,
            ...options
        };
        
        this.suggestionsContainer = null;
        this.previewContainer = null;
        this.searchTimeout = null;
        this.selectedLocation = null;
        
        this.init();
    }
    
    init() {
        if (this.options.placeholder) {
            this.inputElement.placeholder = this.options.placeholder;
        }
        
        this.createSuggestionContainer();
        
        if (this.options.showPreview) {
            this.createPreviewContainer();
        }
        
        this.setupEventListeners();
        
        console.log('✅ Widget inicializado:', this.inputElement.id);
    }
    
    setupEventListeners() {
        this.inputElement.addEventListener('input', (e) => {
            this.handleInput(e.target.value);
        });
        
        this.inputElement.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.clearSuggestions();
            }
        });
        
        document.addEventListener('click', (e) => {
            if (!this.inputElement.contains(e.target) && 
                !this.suggestionsContainer?.contains(e.target)) {
                this.clearSuggestions();
            }
        });
    }
    
    handleInput(value) {
        clearTimeout(this.searchTimeout);
        
        const query = value.trim();
        
        if (query.length < this.options.minChars) {
            this.clearSuggestions();
            return;
        }
        
        this.showLoading();
        
        this.searchTimeout = setTimeout(() => {
            this.search(query);
        }, this.options.debounceTime);
    }
    
    async search(query) {
        try {
            console.log('🔍 Buscando:', query);
            
            const url = `${this.options.apiUrl}?action=search&q=${encodeURIComponent(query)}`;
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const result = await response.json();
            
            console.log('📡 Resultados:', result);
            
            if (result.success && result.data) {
                this.displaySuggestions(result.data);
            } else {
                this.showNoResults();
            }
            
        } catch (error) {
            console.error('❌ Error en búsqueda:', error);
            this.showError('Error al buscar ubicaciones. Por favor, intenta de nuevo.');
        }
    }
    
    createSuggestionContainer() {
        const parent = this.inputElement.parentElement;
        if (getComputedStyle(parent).position === 'static') {
            parent.style.position = 'relative';
        }
        
        this.suggestionsContainer = document.createElement('div');
        this.suggestionsContainer.className = 'ubicacion-suggestions';
        this.suggestionsContainer.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 10px 10px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: none;
            margin-top: 2px;
        `;
        
        parent.appendChild(this.suggestionsContainer);
    }
    
    createPreviewContainer() {
        const previewId = this.options.previewContainerId;
        
        if (!previewId) return;
        
        let container = document.getElementById(previewId);
        
        if (!container) {
            container = document.createElement('div');
            container.id = previewId;
            container.className = 'ubicacion-preview';
            this.inputElement.parentElement.appendChild(container);
        }
        
        this.previewContainer = container;
    }
    
    displaySuggestions(locations) {
        this.clearSuggestions();
        
        if (!locations || locations.length === 0) {
            this.showNoResults();
            return;
        }
        
        locations.forEach(location => {
            const item = this.createSuggestionItem(location);
            this.suggestionsContainer.appendChild(item);
        });
        
        this.suggestionsContainer.style.display = 'block';
    }
    
    createSuggestionItem(location) {
        const item = document.createElement('div');
        item.className = 'suggestion-item';
        item.style.cssText = `
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
            transition: background-color 0.2s ease;
        `;
        
        const icon = this.getIconForType(location.type || 'otro');
        const source = location.source === 'local' ? 
                      '<span style="color: #10b981; font-size: 11px; margin-left: 5px;">★ Usado antes</span>' : '';
        
        item.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 18px;">${icon}</span>
                <div style="flex: 1;">
                    <div style="font-weight: 500; color: #2d3748;">
                        ${location.name || location.display_name}${source}
                    </div>
                    <div style="font-size: 12px; color: #718096;">
                        ${location.display_name}
                    </div>
                </div>
            </div>
        `;
        
        item.addEventListener('mouseenter', () => {
            item.style.backgroundColor = '#f7fafc';
        });
        
        item.addEventListener('mouseleave', () => {
            item.style.backgroundColor = 'transparent';
        });
        
        item.addEventListener('click', () => {
            this.selectLocation(location);
        });
        
        return item;
    }
    
    getIconForType(type) {
        const icons = {
            'ciudad': '🏙️',
            'hotel': '🏨',
            'monumento': '🏛️',
            'aeropuerto': '✈️',
            'estacion': '🚉',
            'parque': '🌳',
            'playa': '🏖️',
            'restaurante': '🍽️',
            'region': '🗺️',
            'pais': '🌍',
            'otro': '📍'
        };
        
        return icons[type] || icons['otro'];
    }
    
    async selectLocation(location) {
        this.selectedLocation = location;
        
        this.inputElement.value = location.display_name;
        
        if (this.options.latInputId && this.options.lngInputId) {
            const latInput = document.getElementById(this.options.latInputId);
            const lngInput = document.getElementById(this.options.lngInputId);
            
            if (latInput) latInput.value = location.lat;
            if (lngInput) lngInput.value = location.lon;
        }
        
        if (this.options.showPreview && this.previewContainer) {
            this.showPreview(location);
        }
        
        if (typeof this.options.onSelect === 'function') {
            this.options.onSelect(location);
        }
        
        this.clearSuggestions();
        
        console.log('✅ Ubicación seleccionada:', location);
    }
    
    showPreview(location) {
        if (!this.previewContainer) return;
        
        const icon = this.getIconForType(location.type);
        
        this.previewContainer.innerHTML = `
            <div style="
                margin-top: 8px;
                padding: 10px;
                background: #f0fdf4;
                border-radius: 8px;
                border-left: 3px solid #10b981;
                font-size: 12px;
            ">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 16px;">${icon}</span>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #065f46;">
                            ${location.name || location.display_name}
                        </div>
                        <div style="color: #059669; font-size: 11px; margin-top: 2px;">
                            📍 ${location.lat.toFixed(6)}, ${location.lon.toFixed(6)}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    showLoading() {
        this.suggestionsContainer.innerHTML = `
            <div style="padding: 20px; text-align: center; color: #718096;">
                <div style="
                    width: 20px;
                    height: 20px;
                    border: 3px solid #e2e8f0;
                    border-top-color: #4299e1;
                    border-radius: 50%;
                    animation: spin 0.8s linear infinite;
                    display: inline-block;
                    margin-bottom: 8px;
                "></div>
                <div>Buscando...</div>
            </div>
        `;
        this.suggestionsContainer.style.display = 'block';
    }
    
    showNoResults() {
        this.suggestionsContainer.innerHTML = `
            <div style="padding: 20px; text-align: center; color: #718096;">
                🔍 No se encontraron ubicaciones
            </div>
        `;
        this.suggestionsContainer.style.display = 'block';
    }
    
    showError(message) {
        this.suggestionsContainer.innerHTML = `
            <div style="padding: 20px; text-align: center; color: #e53e3e;">
                ⚠️ ${message}
            </div>
        `;
        this.suggestionsContainer.style.display = 'block';
    }
    
    clearSuggestions() {
        if (this.suggestionsContainer) {
            this.suggestionsContainer.innerHTML = '';
            this.suggestionsContainer.style.display = 'none';
        }
    }
    
    destroy() {
        if (this.suggestionsContainer) {
            this.suggestionsContainer.remove();
        }
        if (this.previewContainer) {
            this.previewContainer.innerHTML = '';
        }
        clearTimeout(this.searchTimeout);
    }
}

window.UbicacionSearchWidget = UbicacionSearchWidget;

// Agregar estilos para la animación de loading
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);