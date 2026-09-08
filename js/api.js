// API Configuration - set your backend URL here
// For local development: const API_BASE = 'http://localhost/arizu-arimato/backend/index.php';
// For production: const API_BASE = 'https://yourdomain.com/backend/index.php';
const API_BASE = 'https://yourdomain.com/backend/index.php';

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
