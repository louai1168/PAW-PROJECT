const CONFIG = {
    API_BASE: '/paw1/backend/api',
    
    APP_NAME: 'Student Attendance System',
    
    DEBUG: true
};

function apiUrl(endpoint) {
    return CONFIG.API_BASE + '/' + endpoint;
}

function getApiBasePath() {
    const depth = window.location.pathname.split('/').filter(p => p).length;
    let basePath = '';

    if (CONFIG.API_BASE.startsWith('/')) {
        return CONFIG.API_BASE;
    }

    for (let i = 1; i < depth; i++) {
        basePath += '../';
    }
    return basePath + 'backend/api';
}
