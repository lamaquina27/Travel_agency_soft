// =====================================
// SISTEMA DE UBICACIÓN ULTRA RÁPIDO
// Optimizado para velocidad máxima
// =====================================

class FastLocationSystem {
    constructor() {
        this.cache = new Map();
        this.searchTimeout = null;
        this.debounceTime = 150; // Reducido de 300ms a 150ms
        this.maxResults = 5; // Reducido para mayor velocidad
        this.isLoading = false;
        
        // Solo una API rápida (Photon es la más rápida)
        this.apiUrl = 'https://photon.komoot.io/api/';
        
        // Cache de lugares populares precargados
        this.popularPlaces = new Map();
        this.initializePopularCache();
        
        // Pool de conexiones para reutilizar
        this.connectionPool = [];
    }

    // Precargar lugares populares para respuesta instantánea
    initializePopularCache() {
        const popular = [
            // Ciudades principales España
            { name: 'Madrid, España', lat: 40.4168, lng: -3.7038 },
            { name: 'Barcelona, España', lat: 41.3851, lng: 2.1734 },
            { name: 'Valencia, España', lat: 39.4699, lng: -0.3763 },
            { name: 'Sevilla, España', lat: 37.3891, lng: -5.9845 },
            { name: 'Bilbao, España', lat: 43.2627, lng: -2.9253 },
            
            // Ciudades internacionales
            { name: 'París, Francia', lat: 48.8566, lng: 2.3522 },
            { name: 'Londres, Reino Unido', lat: 51.5074, lng: -0.1278 },
            { name: 'Roma, Italia', lat: 41.9028, lng: 12.4964 },
            { name: 'Berlín, Alemania', lat: 52.5200, lng: 13.4050 },
            { name: 'Ámsterdam, Países Bajos', lat: 52.3676, lng: 4.9041 },
            
            // Destinos turísticos
            { name: 'Cancún, México', lat: 21.1619, lng: -86.8515 },
            { name: 'Punta Cana, República Dominicana', lat: 18.5601, lng: -68.3725 },
            { name: 'Miami, Estados Unidos', lat: 25.7617, lng: -80.1918 },
            { name: 'Nueva York, Estados Unidos', lat: 40.7128, lng: -74.0060 },
            { name: 'Tokio, Japón', lat: 35.6762, lng: 139.6503 }
        ];
        
        popular.forEach(place => {
            // Múltiples variaciones para búsqueda más flexible
            const variations = [
                place.name.toLowerCase(),
                place.name.split(',')[0].toLowerCase(),
                place.name.replace(/,.*/, '').toLowerCase()
            ];
            
            variations.forEach(key => {
                this.popularPlaces.set(key, place);
            });
        });
    }

    // Configurar campo con optimizaciones
    setupField(fieldId, coordinateFields = null) {
        const field = document.getElementById(fieldId);
        if (!field) return;

        // Crear contenedor de sugerencias
        this.createSuggestionsContainer(field);
        
        // Event listeners optimizados
        field.addEventListener('input', (e) => this.handleInput(e, coordinateFields));
        field.addEventListener('keydown', (e) => this.handleKeyboard(e));
        field.addEventListener('blur', () => this.hideSuggestions(field, 200));
        field.addEventListener('focus', (e) => this.handleFocus(e, coordinateFields));
        
        // Marcar como configurado
        field.dataset.fastLocationSetup = 'true';
    }

    // Manejar entrada de texto con optimizaciones
    handleInput(e, coordinateFields) {
        const field = e.target;
        const query = field.value.trim();
        
        // Limpiar timeout anterior
        clearTimeout(this.searchTimeout);
        
        if (query.length < 2) {
            this.hideSuggestions(field);
            return;
        }

        // Búsqueda instantánea en cache popular
        const quickResults = this.searchPopularCache(query);
        if (quickResults.length > 0) {
            this.showSuggestions(field, quickResults, coordinateFields);
        }

        // Búsqueda en cache normal
        const cacheKey = query.toLowerCase();
        if (this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < 300000) { // 5 minutos cache
                this.showSuggestions(field, cached.results, coordinateFields);
                return;
            }
        }

        // Nueva búsqueda con debounce reducido
        this.searchTimeout = setTimeout(() => {
            this.searchLocation(query, field, coordinateFields);
        }, this.debounceTime);
    }

    // Búsqueda instantánea en lugares populares
    searchPopularCache(query) {
        const results = [];
        const searchTerm = query.toLowerCase();
        
        for (const [key, place] of this.popularPlaces) {
            if (key.includes(searchTerm) && results.length < 3) {
                results.push({
                    ...place,
                    type: 'popular',
                    score: this.calculateScore(searchTerm, key)
                });
            }
        }
        
        return results.sort((a, b) => b.score - a.score);
    }

    // Calcular puntuación de relevancia
    calculateScore(query, text) {
        let score = 0;
        
        if (text.startsWith(query)) score += 100;
        else if (text.includes(query)) score += 50;
        
        // Bonificación por ciudades principales
        if (text.includes('madrid') || text.includes('barcelona') || text.includes('parís')) {
            score += 25;
        }
        
        return score;
    }

    // Búsqueda API optimizada
    async searchLocation(query, field, coordinateFields) {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading(field);

        try {
            // Usar AbortController para cancelar peticiones anteriores
            if (this.currentController) {
                this.currentController.abort();
            }
            
            this.currentController = new AbortController();
            
            // URL optimizada - solo campos necesarios
            const url = `${this.apiUrl}?q=${encodeURIComponent(query)}&limit=${this.maxResults}&lang=es`;
            
            const response = await fetch(url, {
                signal: this.currentController.signal,
                headers: {
                    'Accept': 'application/json',
                    'Accept-Encoding': 'gzip, deflate'
                }
            });

            if (!response.ok) throw new Error('Network error');
            
            const data = await response.json();
            const results = this.processResults(data.features || []);
            
            // Guardar en cache
            this.cache.set(query.toLowerCase(), {
                results: results,
                timestamp: Date.now()
            });
            
            this.showSuggestions(field, results, coordinateFields);
            
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.warn('Error en búsqueda rápida:', error);
                this.showError(field);
            }
        } finally {
            this.isLoading = false;
            this.hideLoading(field);
        }
    }

    // Procesar resultados de forma optimizada
    processResults(features) {
        return features.slice(0, this.maxResults).map(feature => {
            const props = feature.properties;
            const coords = feature.geometry.coordinates;
            
            return {
                name: this.formatLocationName(props),
                lat: coords[1],
                lng: coords[0],
                type: props.type || 'location'
            };
        });
    }

    // Formatear nombre de ubicación
    formatLocationName(props) {
        const parts = [];
        
        if (props.name) parts.push(props.name);
        if (props.city && props.city !== props.name) parts.push(props.city);
        if (props.country) parts.push(props.country);
        
        return parts.slice(0, 3).join(', ');
    }

    // Mostrar sugerencias optimizado
    showSuggestions(field, results, coordinateFields) {
        const container = field.parentNode.querySelector('.fast-suggestions');
        if (!container) return;

        if (results.length === 0) {
            container.style.display = 'none';
            return;
        }

        const html = results.map((result, index) => `
            <div class="suggestion-item ${index === 0 ? 'highlighted' : ''}" 
                 data-index="${index}"
                 data-lat="${result.lat}" 
                 data-lng="${result.lng}"
                 data-name="${result.name}">
                <div class="suggestion-main">
                    <i class="fas fa-map-marker-alt"></i>
                    <span class="suggestion-name">${result.name}</span>
                </div>
                ${result.type === 'popular' ? '<span class="popular-badge">Popular</span>' : ''}
            </div>
        `).join('');

        container.innerHTML = html;
        container.style.display = 'block';

        // Event listeners para clics
        container.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('mousedown', (e) => {
                e.preventDefault();
                this.selectSuggestion(field, item, coordinateFields);
            });
        });
    }

    // Seleccionar sugerencia
    selectSuggestion(field, item, coordinateFields) {
        const name = item.dataset.name;
        const lat = parseFloat(item.dataset.lat);
        const lng = parseFloat(item.dataset.lng);

        field.value = name;
        
        // Actualizar coordenadas si se proporcionan campos
        if (coordinateFields) {
            this.updateCoordinates(coordinateFields, lat, lng);
        }

        // Actualizar mapa si existe
        if (window.updateMapLocation) {
            window.updateMapLocation(lat, lng);
        }

        this.hideSuggestions(field);
        
        // Disparar evento personalizado
        field.dispatchEvent(new CustomEvent('locationSelected', {
            detail: { name, lat, lng }
        }));
    }

    // Actualizar coordenadas en campos específicos
    updateCoordinates(coordinateFields, lat, lng) {
        if (coordinateFields.lat) {
            const latField = document.getElementById(coordinateFields.lat);
            if (latField) latField.value = lat;
        }
        
        if (coordinateFields.lng) {
            const lngField = document.getElementById(coordinateFields.lng);
            if (lngField) lngField.value = lng;
        }
    }

    // Crear contenedor de sugerencias
    createSuggestionsContainer(field) {
        // Verificar si ya existe
        let container = field.parentNode.querySelector('.fast-suggestions');
        if (container) return;

        container = document.createElement('div');
        container.className = 'fast-suggestions';
        container.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;

        // Asegurar posicionamiento relativo del padre
        const parent = field.parentNode;
        if (getComputedStyle(parent).position === 'static') {
            parent.style.position = 'relative';
        }

        parent.appendChild(container);
        
        // Agregar estilos CSS si no existen
        if (!document.getElementById('fast-location-styles')) {
            this.addOptimizedCSS();
        }
    }

    // Agregar CSS optimizado
    addOptimizedCSS() {
        const style = document.createElement('style');
        style.id = 'fast-location-styles';
        style.textContent = `
            .suggestion-item {
                padding: 12px 16px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-bottom: 1px solid #f0f0f0;
                transition: background-color 0.1s;
            }
            
            .suggestion-item:hover,
            .suggestion-item.highlighted {
                background-color: #f8f9fa;
            }
            
            .suggestion-main {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .suggestion-main i {
                color: #6c757d;
                width: 14px;
            }
            
            .suggestion-name {
                font-size: 14px;
                color: #333;
            }
            
            .popular-badge {
                background: #007bff;
                color: white;
                font-size: 10px;
                padding: 2px 6px;
                border-radius: 10px;
                font-weight: 600;
            }
            
            .loading-indicator {
                padding: 8px 16px;
                color: #6c757d;
                font-size: 12px;
                text-align: center;
            }
        `;
        document.head.appendChild(style);
    }

    // Manejar teclado
    handleKeyboard(e) {
        const container = e.target.parentNode.querySelector('.fast-suggestions');
        if (!container || container.style.display === 'none') return;

        const items = container.querySelectorAll('.suggestion-item');
        const highlighted = container.querySelector('.suggestion-item.highlighted');
        let currentIndex = highlighted ? parseInt(highlighted.dataset.index) : -1;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                currentIndex = Math.min(currentIndex + 1, items.length - 1);
                this.highlightItem(items, currentIndex);
                break;
            case 'ArrowUp':
                e.preventDefault();
                currentIndex = Math.max(currentIndex - 1, 0);
                this.highlightItem(items, currentIndex);
                break;
            case 'Enter':
                e.preventDefault();
                if (highlighted) {
                    highlighted.click();
                }
                break;
            case 'Escape':
                this.hideSuggestions(e.target);
                break;
        }
    }

    // Resaltar elemento
    highlightItem(items, index) {
        items.forEach(item => item.classList.remove('highlighted'));
        if (items[index]) {
            items[index].classList.add('highlighted');
        }
    }

    // Ocultar sugerencias
    hideSuggestions(field, delay = 0) {
        setTimeout(() => {
            const container = field.parentNode.querySelector('.fast-suggestions');
            if (container) {
                container.style.display = 'none';
            }
        }, delay);
    }

    // Mostrar indicador de carga
    showLoading(field) {
        const container = field.parentNode.querySelector('.fast-suggestions');
        if (container) {
            container.innerHTML = '<div class="loading-indicator">🔍 Buscando...</div>';
            container.style.display = 'block';
        }
    }

    // Ocultar indicador de carga
    hideLoading(field) {
        // El loading se oculta cuando se muestran resultados
    }

    // Mostrar error
    showError(field) {
        const container = field.parentNode.querySelector('.fast-suggestions');
        if (container) {
            container.innerHTML = '<div class="loading-indicator">⚠️ Error en búsqueda</div>';
            setTimeout(() => this.hideSuggestions(field), 2000);
        }
    }

    // Manejar focus para mostrar sugerencias recientes
    handleFocus(e, coordinateFields) {
        const field = e.target;
        const query = field.value.trim();
        
        if (query.length >= 2) {
            // Mostrar resultados del cache si existen
            const cacheKey = query.toLowerCase();
            if (this.cache.has(cacheKey)) {
                const cached = this.cache.get(cacheKey);
                this.showSuggestions(field, cached.results, coordinateFields);
            }
        }
    }

    // Limpiar cache (llamar periódicamente)
    clearOldCache() {
        const now = Date.now();
        for (const [key, value] of this.cache) {
            if (now - value.timestamp > 900000) { // 15 minutos
                this.cache.delete(key);
            }
        }
    }
}

// =====================================
// INICIALIZACIÓN AUTOMÁTICA
// =====================================

// Instancia global
window.fastLocationSystem = new FastLocationSystem();

// Función de configuración rápida para formularios
function setupFastLocation(fieldId, coordinateFields = null) {
    window.fastLocationSystem.setupField(fieldId, coordinateFields);
}

// Auto-configuración al cargar DOM
document.addEventListener('DOMContentLoaded', function() {
    // Configurar campos comunes automáticamente
    setupFastLocation('ubicacion', { lat: 'latitud', lng: 'longitud' });
    setupFastLocation('lugar_salida', { lat: 'lat_salida', lng: 'lng_salida' });
    setupFastLocation('lugar_llegada', { lat: 'lat_llegada', lng: 'lng_llegada' });
    
    // Limpiar cache cada 15 minutos
    setInterval(() => {
        window.fastLocationSystem.clearOldCache();
    }, 900000);
});

// Función para configurar dinámicamente nuevos campos
function setupLocationField(fieldId, latFieldId = null, lngFieldId = null) {
    const coordinateFields = latFieldId && lngFieldId ? 
        { lat: latFieldId, lng: lngFieldId } : null;
    
    setupFastLocation(fieldId, coordinateFields);
}

console.log('🚀 Sistema de ubicación ultra rápido cargado');