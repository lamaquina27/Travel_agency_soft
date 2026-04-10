// =====================================
// SISTEMA MODERNO DE CARGA DE IMÁGENES MÚLTIPLES
// Para DÍAS y ACTIVIDADES de la Biblioteca
// =====================================

// Variables globales
let imageManager = {
    selectedFiles: [],        // Archivos nuevos seleccionados
    existingImages: [],       // Imágenes ya guardadas en BD
    maxImages: 3,             // Máximo de imágenes
    maxSizeMB: 10,            // Tamaño máximo por imagen
    allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
    currentResourceId: null,  // ID del recurso actual (para edición)
    currentResourceType: null // Tipo: 'dias' o 'actividades'
};

/**
 * Inicializar el sistema de imágenes
 * Llamar cuando se abre el modal de crear/editar
 */
function initImageUploadSystem(resourceType, resourceId = null, existingImagesData = []) {
    console.log('🎨 Inicializando sistema de imágenes...');
    
    // Resetear estado
    imageManager.selectedFiles = [];
    imageManager.existingImages = existingImagesData;
    imageManager.currentResourceId = resourceId;
    imageManager.currentResourceType = resourceType;
    
    // Renderizar interfaz
    renderImageInterface();
    
    // Setup event listeners
    setupImageEventListeners();
    
    console.log('✅ Sistema de imágenes listo');
}

/**
 * Renderizar la interfaz completa de imágenes
 */
function renderImageInterface() {
    const container = document.getElementById('imageUploadContainer');
    if (!container) {
        console.error('❌ Contenedor imageUploadContainer no encontrado');
        return;
    }
    
    const totalImages = imageManager.existingImages.length + imageManager.selectedFiles.length;
    const canAddMore = totalImages < imageManager.maxImages;
    
    container.innerHTML = `
        <div class="modern-image-upload">
            <!-- Zona de Drop (solo si puede agregar más) -->
            ${canAddMore ? `
                <div class="drop-zone" id="dropZone">
                    <div class="drop-zone-content">
                        <div class="drop-zone-icon">📁</div>
                        <div class="drop-zone-title">Arrastra hasta ${imageManager.maxImages - totalImages} imagen(es) aquí</div>
                        <div class="drop-zone-subtitle">o haz clic para seleccionar</div>
                        <div class="drop-zone-info">JPEG, PNG, WebP • Máximo ${imageManager.maxSizeMB}MB por imagen</div>
                    </div>
                    <input 
                        type="file" 
                        id="fileInput" 
                        accept="${imageManager.allowedTypes.join(',')}" 
                        multiple 
                        style="display: none;">
                </div>
            ` : ''}
            
            <!-- Grid de Cards -->
            <div class="images-grid" id="imagesGrid">
                ${renderImageCards()}
            </div>
            
            <!-- Contador -->
            <div class="images-counter">
                <span class="counter-badge ${totalImages === imageManager.maxImages ? 'full' : ''}">
                    ${totalImages} / ${imageManager.maxImages} imágenes
                </span>
            </div>
            
            <!-- Inputs ocultos para el formulario -->
            <input type="hidden" name="imagen1" id="hiddenInput1">
            <input type="hidden" name="imagen2" id="hiddenInput2">
            <input type="hidden" name="imagen3" id="hiddenInput3">
        </div>
    `;
}

/**
 * Renderizar las tarjetas de imágenes (existentes + nuevas)
 */
function renderImageCards() {
    let html = '';
    
    // Renderizar imágenes existentes
    imageManager.existingImages.forEach((img, index) => {
        html += `
            <div class="image-card existing" data-index="${index}" data-type="existing">
                <div class="image-card-preview">
                    <img src="${img.url}" alt="Imagen ${index + 1}">
                </div>
                <div class="image-card-overlay">
                    <button type="button" class="btn-delete-image" onclick="deleteExistingImage(${index}, '${img.field}')">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
                <div class="image-card-badge">Imagen ${index + 1}</div>
                <div class="image-card-drag-handle">⋮⋮</div>
            </div>
        `;
    });
    
    // Renderizar nuevas imágenes seleccionadas
    imageManager.selectedFiles.forEach((file, index) => {
        const realIndex = imageManager.existingImages.length + index;
        html += `
            <div class="image-card new" data-index="${index}" data-type="new">
                <div class="image-card-preview">
                    <img src="${file.preview}" alt="${file.name}">
                </div>
                <div class="image-card-overlay">
                    <button type="button" class="btn-delete-image" onclick="deleteNewImage(${index})">
                        <i class="fas fa-times"></i> Quitar
                    </button>
                </div>
                <div class="image-card-info">
                    <div class="image-card-name">${truncateFilename(file.name)}</div>
                    <div class="image-card-size">${formatFileSize(file.size)}</div>
                </div>
                <div class="image-card-badge">Nueva ${realIndex + 1}</div>
                <div class="image-card-drag-handle">⋮⋮</div>
            </div>
        `;
    });
    
    return html || '<div class="no-images-message">No hay imágenes agregadas</div>';
}

/**
 * Configurar event listeners
 */
function setupImageEventListeners() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    
    if (dropZone && fileInput) {
        // Click en zona de drop
        dropZone.addEventListener('click', () => fileInput.click());
        
        // Drag & Drop events
        dropZone.addEventListener('dragover', handleDragOver);
        dropZone.addEventListener('dragleave', handleDragLeave);
        dropZone.addEventListener('drop', handleDrop);
        
        // File input change
        fileInput.addEventListener('change', (e) => handleFileSelect(e.target.files));
    }
    
    // Setup drag & drop para reordenar (si hay imágenes)
    setupDragAndDropReorder();
}

/**
 * Manejar drag over
 */
function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.classList.add('drag-over');
}

/**
 * Manejar drag leave
 */
function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.classList.remove('drag-over');
}

/**
 * Manejar drop de archivos
 */
function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.classList.remove('drag-over');
    
    const files = e.dataTransfer.files;
    handleFileSelect(files);
}

/**
 * Manejar selección de archivos
 */
function handleFileSelect(files) {
    console.log(`📂 ${files.length} archivo(s) seleccionado(s)`);
    
    const filesArray = Array.from(files);
    const totalCurrent = imageManager.existingImages.length + imageManager.selectedFiles.length;
    const availableSlots = imageManager.maxImages - totalCurrent;
    
    if (availableSlots === 0) {
        showToast('Ya tienes el máximo de imágenes (3)', 'warning');
        return;
    }
    
    // Tomar solo las que caben
    const filesToAdd = filesArray.slice(0, availableSlots);
    
    if (filesToAdd.length < filesArray.length) {
        showToast(`Solo se pueden agregar ${filesToAdd.length} imagen(es) más`, 'warning');
    }
    
    // Validar y agregar cada archivo
    filesToAdd.forEach(file => {
        if (validateImageFile(file)) {
            addImageFile(file);
        }
    });
    
    // Re-renderizar
    renderImageInterface();
    setupImageEventListeners();
    updateHiddenInputs();
}

/**
 * Validar archivo de imagen
 */
function validateImageFile(file) {
    // Validar tipo
    if (!imageManager.allowedTypes.includes(file.type.toLowerCase())) {
        showToast(`"${file.name}" no es un formato válido. Use: JPEG, PNG, WebP`, 'error');
        return false;
    }
    
    // Validar tamaño
    const maxSizeBytes = imageManager.maxSizeMB * 1024 * 1024;
    if (file.size > maxSizeBytes) {
        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        showToast(`"${file.name}" es muy grande (${sizeMB}MB). Máximo: ${imageManager.maxSizeMB}MB`, 'error');
        return false;
    }
    
    return true;
}

/**
 * Agregar archivo al manager
 */
function addImageFile(file) {
    // Crear preview
    const reader = new FileReader();
    reader.onload = (e) => {
        imageManager.selectedFiles.push({
            file: file,
            name: file.name,
            size: file.size,
            preview: e.target.result
        });
        
        // Re-renderizar después de cargar el preview
        renderImageInterface();
        setupImageEventListeners();
        updateHiddenInputs();
    };
    reader.readAsDataURL(file);
}

/**
 * Eliminar imagen existente (del servidor)
 */
async function deleteExistingImage(index, field) {
    if (!confirm('¿Eliminar esta imagen del servidor? Esta acción no se puede deshacer.')) {
        return;
    }
    
    const img = imageManager.existingImages[index];
    
    console.log('🗑️ Eliminando imagen existente:', field);
    
    try {
        // Llamar al endpoint de eliminación
        const response = await fetch(`${APP_URL}/modules/biblioteca/delete_image_api.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                type: imageManager.currentResourceType,
                resource_id: imageManager.currentResourceId,
                image_field: field
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Remover del array
            imageManager.existingImages.splice(index, 1);
            
            // Re-renderizar
            renderImageInterface();
            setupImageEventListeners();
            
            showToast('Imagen eliminada correctamente', 'success');
        } else {
            throw new Error(result.error || 'Error al eliminar');
        }
        
    } catch (error) {
        console.error('Error eliminando imagen:', error);
        showToast('Error al eliminar la imagen: ' + error.message, 'error');
    }
}

/**
 * Eliminar nueva imagen (solo del array, no del servidor)
 */
function deleteNewImage(index) {
    console.log('🗑️ Quitando imagen nueva:', index);
    
    // Liberar URL del preview
    URL.revokeObjectURL(imageManager.selectedFiles[index].preview);
    
    // Remover del array
    imageManager.selectedFiles.splice(index, 1);
    
    // Re-renderizar
    renderImageInterface();
    setupImageEventListeners();
    updateHiddenInputs();
    
    showToast('Imagen quitada', 'info');
}

/**
 * Actualizar inputs ocultos para enviar en el formulario
 */
function updateHiddenInputs() {
    console.log('🔄 Actualizando inputs ocultos...');
    
    // Limpiar inputs
    for (let i = 1; i <= 3; i++) {
        const input = document.getElementById(`hiddenInput${i}`);
        if (input) {
            input.value = '';
            input.removeAttribute('data-file-index');
        }
    }
    
    // Asignar nuevos archivos a los slots disponibles
    const occupiedSlots = imageManager.existingImages.map(img => {
        const match = img.field.match(/\d+/);
        return match ? parseInt(match[0]) : 0;
    });
    
    console.log('🔒 Slots ocupados por existentes:', occupiedSlots);
    
    let fileIndex = 0;
    for (let slot = 1; slot <= 3 && fileIndex < imageManager.selectedFiles.length; slot++) {
        if (!occupiedSlots.includes(slot)) {
            const input = document.getElementById(`hiddenInput${slot}`);
            if (input) {
                input.setAttribute('data-file-index', fileIndex);
                console.log(`📎 Archivo ${fileIndex} asignado a slot ${slot}`);
                fileIndex++;
            }
        }
    }
}

/**
 * Obtener los archivos para enviar en FormData
 * Llamar antes de enviar el formulario
 */
function getFilesForSubmit() {
    const files = {};
    
    for (let i = 1; i <= 3; i++) {
        const input = document.getElementById(`hiddenInput${i}`);
        if (input && input.hasAttribute('data-file-index')) {
            const fileIndex = parseInt(input.getAttribute('data-file-index'));
            if (imageManager.selectedFiles[fileIndex]) {
                files[`imagen${i}`] = imageManager.selectedFiles[fileIndex].file;
            }
        }
    }
    
    console.log('📤 Archivos para enviar:', Object.keys(files));
    return files;
}

/**
 * Setup drag and drop para reordenar imágenes
 */
function setupDragAndDropReorder() {
    const cards = document.querySelectorAll('.image-card');
    
    cards.forEach(card => {
        card.setAttribute('draggable', true);
        
        card.addEventListener('dragstart', (e) => {
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', card.innerHTML);
        });
        
        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
        });
        
        card.addEventListener('dragover', (e) => {
            e.preventDefault();
            const dragging = document.querySelector('.dragging');
            const grid = document.getElementById('imagesGrid');
            
            if (dragging && grid) {
                const afterElement = getDragAfterElement(grid, e.clientX);
                if (afterElement == null) {
                    grid.appendChild(dragging);
                } else {
                    grid.insertBefore(dragging, afterElement);
                }
            }
        });
    });
}

/**
 * Obtener elemento después del cual insertar
 */
function getDragAfterElement(container, x) {
    const draggableElements = [...container.querySelectorAll('.image-card:not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = x - box.left - box.width / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

/**
 * Aplicar el nuevo orden antes de guardar
 * Llamar cuando el usuario termina de reordenar
 */
function applyImageOrder() {
    const grid = document.getElementById('imagesGrid');
    if (!grid) return;
    
    const cards = grid.querySelectorAll('.image-card');
    const newExisting = [];
    const newSelected = [];
    
    cards.forEach(card => {
        const type = card.getAttribute('data-type');
        const index = parseInt(card.getAttribute('data-index'));
        
        if (type === 'existing') {
            newExisting.push(imageManager.existingImages[index]);
        } else if (type === 'new') {
            newSelected.push(imageManager.selectedFiles[index]);
        }
    });
    
    imageManager.existingImages = newExisting;
    imageManager.selectedFiles = newSelected;
    
    console.log('✅ Orden aplicado');
}

// =====================================
// FUNCIONES AUXILIARES
// =====================================

function truncateFilename(filename, maxLength = 20) {
    if (filename.length <= maxLength) return filename;
    const ext = filename.split('.').pop();
    const name = filename.substring(0, maxLength - ext.length - 4);
    return `${name}...${ext}`;
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1024 / 1024).toFixed(2) + ' MB';
}

function showToast(message, type = 'info') {
    // Implementación de toast notification
    console.log(`[${type.toUpperCase()}] ${message}`);
    
    // Puedes usar tu sistema de notificaciones existente
    if (typeof showMessage === 'function') {
        showMessage(message, type);
    } else {
        alert(message);
    }
}

/**
 * Limpiar todo al cerrar el modal
 */
function cleanupImageSystem() {
    // Liberar URLs de previews
    imageManager.selectedFiles.forEach(file => {
        if (file.preview) {
            URL.revokeObjectURL(file.preview);
        }
    });
    
    // Resetear
    imageManager.selectedFiles = [];
    imageManager.existingImages = [];
    imageManager.currentResourceId = null;
    imageManager.currentResourceType = null;
    
    console.log('🧹 Sistema de imágenes limpiado');
}