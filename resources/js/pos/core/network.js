console.log("Core network module loaded");

// =============================
// SAFE ACCESS
// =============================
window.POS = window.POS || {};

// =============================
// NETWORK UI
// =============================
function updateNetworkUI() {

    const el = document.getElementById('networkStatus');
    if (!el) return;

    if (navigator.onLine) {
        el.innerText = 'ONLINE';
        el.classList.remove('bg-red-500');
        el.classList.add('bg-green-500');
    } else {
        el.innerText = 'OFFLINE';
        el.classList.remove('bg-green-500');
        el.classList.add('bg-red-500');
    }
}

// =============================
// OFFLINE UI
// =============================
function updateOfflineUI() {

    const el = document.getElementById('offlineStatus');
    if (!el) return;

    const queue = JSON.parse(localStorage.getItem('offline_sales_queue') || '[]');

    const unsynced = queue.length;

    el.innerText = navigator.onLine
        ? `Online | Unsynced: ${unsynced}`
        : `Offline | Unsynced: ${unsynced}`;
}

// =============================
// REFRESH ALL NETWORK UI
// =============================
function refresh() {
    updateNetworkUI();
    updateOfflineUI();
}

// =============================
// INIT (SAFE BINDING)
// =============================
function initNetwork() {
    refresh();
}

// =============================
// EVENTS
// =============================
window.addEventListener('online', refresh);
window.addEventListener('offline', refresh);
window.addEventListener('load', refresh);

// =============================
// EXPOSE MODULE
// =============================
window.POS.network = {
    refresh
};