<?php
// =====================================
// ARCHIVO: assets/js/api-connections.js - Conexiones API Reales
// =====================================
?>
// Archivo para centralizar todas las conexiones API del sistema
class TravelAgencyAPI {
    constructor() {
        this.baseURL = APP_URL;
        this.defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
    }

    // Método genérico para hacer peticiones
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            ...this.defaultOptions,
            ...options
        };

        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success === false) {
                throw new Error(data.error || 'Error en la respuesta del servidor');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // API de Biblioteca
    async getBibliotecaResources(type, filters = {}) {
        const params = new URLSearchParams({
            action: 'list',
            type: type,
            ...filters
        });
        
        return this.request(`/biblioteca/api?${params}`);
    }

    async createBibliotecaResource(type, data) {
        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('type', type);
        
        Object.keys(data).forEach(key => {
            if (data[key] !== null && data[key] !== undefined) {
                formData.append(key, data[key]);
            }
        });

        return this.request('/biblioteca/api', {
            method: 'POST',
            headers: {}, // Remove content-type for FormData
            body: formData
        });
    }

    async updateBibliotecaResource(type, id, data) {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('type', type);
        formData.append('id', id);
        
        Object.keys(data).forEach(key => {
            if (data[key] !== null && data[key] !== undefined) {
                formData.append(key, data[key]);
            }
        });

        return this.request('/biblioteca/api', {
            method: 'POST',
            headers: {},
            body: formData
        });
    }

    async deleteBibliotecaResource(type, id) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('type', type);
        formData.append('id', id);

        return this.request('/biblioteca/api', {
            method: 'POST',
            headers: {},
            body: formData
        });
    }

    async uploadImage(file) {
        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('image', file);

        return this.request('/biblioteca/api', {
            method: 'POST',
            headers: {},
            body: formData
        });
    }

    // API de Programa
    async getProgramSolicitudes(filters = {}) {
        const params = new URLSearchParams({
            action: 'list',
            ...filters
        });
        
        return this.request(`/programa/api?${params}`);
    }

    async createProgramSolicitud(data) {
        const formData = new FormData();
        formData.append('action', 'create');
        
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });

        return this.request('/programa/api', {
            method: 'POST',
            headers: {},
            body: formData
        });
    }

    async updateProgramSolicitud(id, data) {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('id', id);
        
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });

        return this.request('/programa/api', {
            method: 'POST',
            headers: {},
            body: formData
        });
    }

    async deleteProgramSolicitud(id) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        return this.request('/programa/api', {
            method: 'POST',
            headers: {},
            body: formData
        });
    }

    async duplicateProgramSolicitud(id) {
        const formData = new FormData();
        formData.append('action', 'duplicate');
        formData.append('id', id);

        return this.request('/programa/api', {
            method: 'POST',
            headers: {},
            body: formData
        });
    }

    // API de Administrador
    async getUsers() {
        return this.request('/admin/api?action=users');
    }

    async createUser(data) {
        const formData = new FormData();
        formData.append('action', 'create_user');
        
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });

        return this.request('/admin/api', {
            method: 'POST',
            headers: {},
            body: formData
        });
    }

    async updateUser(id, data) {
        const formData = new FormData();
        formData.append('action', 'update_user');
        formData.append('id', id);
        
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });

        return this.request('/admin/api', {
            method: 'POST',
            headers: {},
            body: formData
        });
    }

    async deleteUser(id) {
        const formData = new FormData();
        formData.append('action', 'delete_user');
        formData.append('id', id);

        return this.request('/admin/api', {
            method: 'POST',
            headers: {},
            body: formData
        });
    }

    async getStatistics() {
        return this.request('/admin/api?action=statistics');
    }
    // API de Bonos
    async getBonoDatos(programa_id) {
        const params = new URLSearchParams({
            action: 'get',
            'programa_id':programa_id
        });
        
        return this.request(`/modules/bonos/api?${params}`);
    }
}

// Instancia global de la API
window.API = new TravelAgencyAPI();

// Utilidades adicionales
class UIHelpers {
    static showLoading(element) {
        if (element) {
            element.style.display = 'block';
        }
    }

    static hideLoading(element) {
        if (element) {
            element.style.display = 'none';
        }
    }

    static showMessage(message, type = 'info') {
        // Crear notificación toast
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'error' ? '#e53e3e' : type === 'success' ? '#2f855a' : '#667eea'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 10000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        document.body.appendChild(toast);
        
        // Animar entrada
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        // Remover después de 3 segundos
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    static confirmAction(message, callback) {
        if (confirm(message)) {
            callback();
        }
    }

    static formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('es-ES');
    }

    static formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleString('es-ES');
    }
}

// Exponer utilidades globalmente
window.UIHelpers = UIHelpers;