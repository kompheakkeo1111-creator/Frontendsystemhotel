// API Configuration - auto-detect backend path
const API_BASE = (function() {
    const origin = window.location.origin;
    return origin + '/arizu-arimato/backend/index.php';
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
