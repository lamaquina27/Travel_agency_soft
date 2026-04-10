/* =====================================================
   BIBLIOTECA CAROUSEL JS - Funcionalidad del carrusel
   ===================================================== */

/**
 * Crear HTML del carrusel con las imágenes disponibles
 */
function createCarouselHTML(images) {
    if (!images || images.length === 0) {
        return '';
    }
    
    // Filtrar imágenes válidas
    const validImages = images.filter(img => img && img.trim() !== '');
    
    if (validImages.length === 0) {
        return '';
    }
    
    const totalImages = validImages.length;
    
    // Generar HTML de las imágenes
    const imagesHTML = validImages.map((imgUrl, index) => 
        `<img src="${imgUrl}" 
             alt="Imagen ${index + 1}" 
             class="carousel-image"
             loading="lazy">`
    ).join('');
    
    // Generar indicadores
    const indicatorsHTML = validImages.map((_, index) => 
        `<div class="carousel-indicator ${index === 0 ? 'active' : ''}" data-index="${index}"></div>`
    ).join('');
    
    // Solo mostrar controles si hay más de 1 imagen
    const showControls = totalImages > 1;
    
    return `
        <div class="card-image-carousel">
            <div class="carousel-images">
                ${imagesHTML}
            </div>
            
            ${showControls ? `
                <button class="carousel-nav prev" aria-label="Imagen anterior">
                    ‹
                </button>
                <button class="carousel-nav next" aria-label="Imagen siguiente">
                    ›
                </button>
                
                <div class="carousel-indicators">
                    ${indicatorsHTML}
                </div>
            ` : ''}
            
            <div class="carousel-counter">📷 1/${totalImages}</div>
        </div>
    `;
}

/**
 * Inicializar todos los carruseles en la página
 */
function initBibliotecaCarousels() {
    console.log('🎠 Inicializando carruseles de biblioteca...');
    
    const carousels = document.querySelectorAll('.card-image-carousel');
    
    if (carousels.length === 0) {
        console.log('⚠️ No se encontraron carruseles');
        return;
    }
    
    carousels.forEach((carousel, index) => {
        initSingleCarousel(carousel, index);
    });
    
    console.log(`✅ ${carousels.length} carrusel(es) inicializados`);
}

/**
 * Inicializar un carrusel individual
 */
function initSingleCarousel(carouselElement, carouselIndex) {
    const images = carouselElement.querySelectorAll('.carousel-image');
    const totalImages = images.length;
    
    // Si solo hay 1 imagen, no necesita controles
    if (totalImages <= 1) {
        const nav = carouselElement.querySelectorAll('.carousel-nav');
        const indicators = carouselElement.querySelector('.carousel-indicators');
        nav.forEach(btn => btn.style.display = 'none');
        if (indicators) indicators.style.display = 'none';
        return;
    }
    
    let currentIndex = 0;
    const imagesContainer = carouselElement.querySelector('.carousel-images');
    
    // Función para actualizar la posición del carrusel
    function updateCarousel() {
        const offset = -currentIndex * 100;
        imagesContainer.style.transform = `translateX(${offset}%)`;
        
        // Actualizar indicadores
        const indicators = carouselElement.querySelectorAll('.carousel-indicator');
        indicators.forEach((indicator, idx) => {
            if (idx === currentIndex) {
                indicator.classList.add('active');
            } else {
                indicator.classList.remove('active');
            }
        });
        
        // Actualizar contador
        const counter = carouselElement.querySelector('.carousel-counter');
        if (counter) {
            counter.textContent = `📷 ${currentIndex + 1}/${totalImages}`;
        }
    }
    
    // Botón anterior
    const prevBtn = carouselElement.querySelector('.carousel-nav.prev');
    if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            currentIndex = (currentIndex - 1 + totalImages) % totalImages;
            updateCarousel();
        });
    }
    
    // Botón siguiente
    const nextBtn = carouselElement.querySelector('.carousel-nav.next');
    if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            currentIndex = (currentIndex + 1) % totalImages;
            updateCarousel();
        });
    }
    
    // Indicadores
    const indicators = carouselElement.querySelectorAll('.carousel-indicator');
    indicators.forEach((indicator, idx) => {
        indicator.addEventListener('click', (e) => {
            e.stopPropagation();
            currentIndex = idx;
            updateCarousel();
        });
    });
    
    // Soporte para swipe en móviles
    let touchStartX = 0;
    let touchEndX = 0;
    
    carouselElement.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });
    
    carouselElement.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, { passive: true });
    
    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                // Swipe left - siguiente
                currentIndex = (currentIndex + 1) % totalImages;
            } else {
                // Swipe right - anterior
                currentIndex = (currentIndex - 1 + totalImages) % totalImages;
            }
            updateCarousel();
        }
    }
    
    // Prevenir que el click en el carrusel abra el modal de edición
    carouselElement.addEventListener('click', (e) => {
        e.stopPropagation();
    });
    
    // Inicializar en la primera imagen
    updateCarousel();
}

/**
 * Re-inicializar carruseles después de renderizar nuevas cards
 */
function reinitCarousels() {
    // Esperar a que el DOM se actualice
    setTimeout(() => {
        initBibliotecaCarousels();
    }, 100);
}

// Auto-inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBibliotecaCarousels);
} else {
    initBibliotecaCarousels();
}

// Exportar funciones globales
window.initBibliotecaCarousels = initBibliotecaCarousels;
window.createCarouselHTML = createCarouselHTML;
window.reinitCarousels = reinitCarousels;

console.log('✅ biblioteca_carousel.js cargado correctamente');