console.log("Offline sync module loaded");

// =============================
// SAFE NAMESPACE
// =============================
window.POS = window.POS || {};
window.POS.offlineSync = window.POS.offlineSync || {};

// =============================
// LOCK (GLOBAL SAFE)
// =============================
let isSyncing = false;

// =============================
// OFFLINE QUEUE KEYS
// =============================
const SALES_QUEUE_KEY = "offline_sales_queue";
const CASH_QUEUE_KEY = "offline_cash_movements";

// =============================
// HELPERS
// =============================
function getCSRF() {
    return document.querySelector('meta[name="csrf-token"]')?.content;
}

// SAFE JSON PARSE (FIX CRASHES)
function safeParse(value) {
    try {
        return JSON.parse(value || "[]");
    } catch (e) {
        console.warn("Corrupt queue reset");
        return [];
    }
}

// =============================
// GETTERS
// =============================
function getSalesQueue() {
    return safeParse(localStorage.getItem(SALES_QUEUE_KEY));
}

function getCashQueue() {
    return safeParse(localStorage.getItem(CASH_QUEUE_KEY));
}

// =============================
// SAVERS
// =============================
function saveSalesQueue(queue) {
    localStorage.setItem(SALES_QUEUE_KEY, JSON.stringify(queue));
}

function saveCashQueue(queue) {
    localStorage.setItem(CASH_QUEUE_KEY, JSON.stringify(queue));
}

// =============================
// UI COUNTS
// =============================
window.POS.offlineSync.getPendingCounts = function () {
    return {
        sales: getSalesQueue().length,
        cash: getCashQueue().length
    };
};

// =============================
// QUEUE SALE
// =============================
window.POS.offlineSync.queueSale = function (sale) {

    const queue = getSalesQueue();

    queue.push({
        id: Date.now(),
        data: structuredClone ? structuredClone(sale) : JSON.parse(JSON.stringify(sale)),
        retries: 0
    });

    saveSalesQueue(queue);

    console.log("Sale queued offline");

    window.dispatchEvent(new Event("offlineSyncUpdated"));
};

// =============================
// QUEUE CASH
// =============================
window.POS.offlineSync.queueCashMovement = function (movement) {

    const queue = getCashQueue();

    queue.push({
        id: Date.now(),
        data: structuredClone ? structuredClone(movement) : JSON.parse(JSON.stringify(movement)),
        retries: 0
    });

    saveCashQueue(queue);

    console.log("Cash movement queued offline");

    window.dispatchEvent(new Event("cashMovementUpdated"));
};

// =============================
// SAFE FETCH WRAPPER
// =============================
async function safePost(url, payload) {

    try {

        const res = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": getCSRF()
            },
            body: JSON.stringify(payload)
        });

        let data = null;

        try {
            data = await res.json();
        } catch (e) {
            console.warn("Invalid JSON response");
            data = {};
        }

        return { ok: res.ok, data };

    } catch (err) {
        return { ok: false, data: {}, error: err };
    }
}

// =============================
// SYNC SALES
// =============================
async function syncSales() {

    let queue = getSalesQueue();
    if (!queue.length) return;

    console.log(`Syncing ${queue.length} sales...`);

    const remaining = [];

    for (let item of queue) {

        const { ok, data } = await safePost("/api/sync-sale", item.data);

        if (ok && data?.success) {
            console.log("✅ Sale synced:", item.id);
            continue;
        }

        console.warn("❌ Sale failed:", item.id);

        item.retries = (item.retries || 0) + 1;

        if (item.retries < 5) {
            remaining.push(item);
        } else {
            console.error("🚨 Dropped sale:", item.id);
        }
    }

    saveSalesQueue(remaining);

    window.dispatchEvent(new Event("offlineSyncUpdated"));
}

// =============================
// SYNC CASH
// =============================
async function syncCash() {

    let queue = getCashQueue();
    if (!queue.length) return;

    console.log(`Syncing ${queue.length} cash movements...`);

    const remaining = [];

    for (let item of queue) {

        const { ok, data } = await safePost("/api/cash-movements/sync", item.data);

        if (ok && data?.success) {
            console.log("✅ Cash synced:", item.id);
            continue;
        }

        console.warn("❌ Cash failed:", item.id);

        item.retries = (item.retries || 0) + 1;

        if (item.retries < 5) {
            remaining.push(item);
        } else {
            console.error("🚨 Dropped cash movement:", item.id);
        }
    }

    saveCashQueue(remaining);

    window.dispatchEvent(new Event("cashMovementUpdated"));
}

// =============================
// MASTER SYNC (LOCKED)
// =============================
async function syncAll() {

    if (!navigator.onLine) return;
    if (isSyncing) return;

    isSyncing = true;

    try {

        console.log("🔄 Sync started");

        await syncSales();
        await syncCash();

        console.log("✅ Sync complete");

    } finally {
        isSyncing = false;
    }
}

// =============================
// TRIGGERS
// =============================
window.addEventListener("online", syncAll);
setInterval(syncAll, 10000);

// =============================
// INIT
// =============================
document.addEventListener("DOMContentLoaded", () => {

    syncAll();

    window.dispatchEvent(new Event("offlineSyncUpdated"));
});