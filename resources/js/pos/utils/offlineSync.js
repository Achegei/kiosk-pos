console.log("Offline sync module loaded");

// =============================
// SAFE NAMESPACE
// =============================
window.POS = window.POS || {};
window.POS.offlineSync = window.POS.offlineSync || {};

// =============================
// CONFIG
// =============================
const SALES_QUEUE_KEY = "offline_sales_queue";
const CASH_QUEUE_KEY = "offline_cash_movements";
const SYNC_LOCK_KEY = "offline_sync_lock";

// =============================
// STATE
// =============================
let isSyncing = false;

// =============================
// SAFE PARSE
// =============================
function safeParse(value) {
    try {
        return JSON.parse(value || "[]");
    } catch (e) {
        console.warn("Corrupt queue reset");
        return [];
    }
}

// =============================
// STORAGE
// =============================
function getSalesQueue() {
    return safeParse(localStorage.getItem(SALES_QUEUE_KEY));
}

function getCashQueue() {
    return safeParse(localStorage.getItem(CASH_QUEUE_KEY));
}

function saveSalesQueue(queue) {
    localStorage.setItem(SALES_QUEUE_KEY, JSON.stringify(queue));
}

function saveCashQueue(queue) {
    localStorage.setItem(CASH_QUEUE_KEY, JSON.stringify(queue));
}

// =============================
// CSRF
// =============================
function getCSRF() {
    return document.querySelector('meta[name="csrf-token"]')?.content;
}

// =============================
// SAFE FETCH (CRITICAL FIX)
// =============================
async function safePost(url, payload) {

    try {
        const res = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify(payload)
        });

        let data = null;

        try {
            data = await res.json();
        } catch {
            data = { success: false };
        }

        return {
            ok: res.ok,
            status: res.status,
            data
        };

    } catch (err) {
        return {
            ok: false,
            error: err
        };
    }
}

// =============================
// QUEUE CASH
// =============================
window.POS.offlineSync.queueCashMovement = function (movement) {

    const queue = getCashQueue();

    queue.push({
        id: crypto.randomUUID?.() || Date.now(),
        data: structuredClone ? structuredClone(movement) : JSON.parse(JSON.stringify(movement)),
        retries: 0,
        status: "pending"
    });

    saveCashQueue(queue);

    window.dispatchEvent(new Event("cashMovementUpdated"));
};

// =============================
// SYNC SALES (FIXED ATOMIC)
// =============================
async function syncSales() {

    const sales = await window.POS.db.getPendingSales();
    if (!sales.length) return;

    console.log(`🔄 Syncing ${sales.length} sales...`);

    const payloads = sales.map(sale => ({
        local_id: sale.local_id,
        customer_id: sale.customer_id,
        payment_method: sale.payment_method,
        mpesa_code: sale.mpesa_code,
        items: (sale.products || sale.items || []).map(i => ({
            product_id: i.id || i.product_id,
            quantity: i.quantity,
            price: i.price
        }))
    }));

    console.log("🚀 SYNC PAYLOAD:", payloads);

    const res = await fetch("/api/offline-sync", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-DEVICE-ID": window.POS.device?.uuid || localStorage.getItem("device_uuid")
    },
    body: JSON.stringify({
        sales: payloads
    })
});

    let data = null;

    try {
        data = await res.json();
    } catch {
        data = { success: false };
    }

    if (!res.ok || !data?.status === 'success') {
        console.error("❌ Sync failed:", res, data);
        return;
    }

    for (let sale of sales) {
        await window.POS.db.markSaleSynced(sale.local_id);
    }

    console.log("✅ Sync cycle complete");
}

// =============================
// SYNC CASH
// =============================
async function syncCash() {

    let queue = getCashQueue();
    if (!queue.length) return;

    const remaining = [];

    for (let item of queue) {

        const { ok, data } = await safePost("/api/cash-movements/sync", item.data);

        if (ok && data?.success) {

            console.log("✅ Cash synced:", item.id);

            window.dispatchEvent(new CustomEvent("cashSynced", {
                detail: data
            }));

            continue;
        }

        item.retries++;

        if (item.retries < 5) {
            remaining.push(item);
        } else {
            console.error("❌ Dropped cash:", item.id);
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
// AUTO SYNC
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