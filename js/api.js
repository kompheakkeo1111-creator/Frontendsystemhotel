// API Configuration - auto-detect backend path
const API_BASE = (function() {
    const origin = window.location.origin;
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        return origin + '/hotel_management2.1.1/hotel_management2.1/index.php';
    }
    return origin + '/hotel_management2.1.1/hotel_management2.1/index.php';
})();

// API Helper
async function apiGet(endpoint) {
    const url = `${API_BASE}?r=api/${endpoint}`;
    const res = await fetch(url);
    return await res.json();
}

async function apiPost(endpoint, data) {
    const url = `${API_BASE}?r=api/${endpoint}`;
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return await res.json();
}

// Format price
function formatPrice(amount, currency = 'USD') {
    return `${currency} ${parseFloat(amount).toFixed(2)}`;
}

// Calculate nights between dates
function calcNights(checkIn, checkOut) {
    const diff = new Date(checkOut) - new Date(checkIn);
    return Math.max(1, Math.ceil(diff / 86400000));
}
