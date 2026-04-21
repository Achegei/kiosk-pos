console.log("Offline sync module loaded");

// =============================
// SAFE NAMESPACE
// =============================
window.POS = window.POS || {};
window.POS.offlineSync = window.POS.offlineSync || {};

// =============================
// LOCK (PREVENT DOUBLE SYNC)
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

// =============================
// GETTERS
// =============================
function getSalesQueue() {
    return JSON.parse(localStorage.getItem(SALES_QUEUE_KEY) || "[]");
}

function getCashQueue() {
    return JSON.parse(localStorage.getItem(CASH_QUEUE_KEY) || "[]");
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
// QUEUE COUNT (FOR UI)
// =============================
window.POS.offlineSync.getPendingCounts = function () {
    return {
        sales: getSalesQueue().length,
        cash: getCashQueue().length
    };
};

// =============================
// ADD SALE TO QUEUE
// =============================
window.POS.offlineSync.queueSale = function (sale) {

    const queue = getSalesQueue();

    queue.push({
        id: Date.now(),
        data: sale,
        retries: 0
    });

    saveSalesQueue(queue);

    console.log("Sale queued offline:", sale);

    window.dispatchEvent(new Event("offlineSyncUpdated"));
};

// =============================
// ADD CASH MOVEMENT TO QUEUE
// =============================
window.POS.offlineSync.queueCashMovement = function (movement) {

    const queue = getCashQueue();

    queue.push({
        id: Date.now(),
        data: movement,
        retries: 0
    });

    saveCashQueue(queue);

    console.log("Cash movement queued offline:", movement);

    window.dispatchEvent(new Event("cashMovementUpdated"));
};

// =============================
// SYNC SALES
// =============================
async function syncSales() {

    let queue = getSalesQueue();
    if (!queue.length) return;

    console.log(`Syncing ${queue.length} sales...`);

    const remaining = [];

    for (let item of queue) {

        try {

            const res = await fetch("/api/sync-sale", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": getCSRF()
                },
                body: JSON.stringify(item.data)
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error("Server rejected sale");
            }

            console.log("✅ Sale synced:", item.id);

        } catch (err) {

            console.warn("❌ Sale sync failed, will retry:", item.id);

            item.retries = (item.retries || 0) + 1;

            // optional: drop after too many retries
            if (item.retries < 5) {
                remaining.push(item);
            } else {
                console.error("🚨 Dropping sale after 5 retries:", item);
            }
        }
    }

    saveSalesQueue(remaining);

    window.dispatchEvent(new Event("offlineSyncUpdated"));
}

// =============================
// SYNC CASH MOVEMENTS
// =============================
async function syncCash() {

    let queue = getCashQueue();
    if (!queue.length) return;

    console.log(`Syncing ${queue.length} cash movements...`);

    const remaining = [];

    for (let item of queue) {

        try {

            const res = await fetch("/api/cash-movements/sync", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": getCSRF()
                },
                body: JSON.stringify(item.data)
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error("Server rejected movement");
            }

            console.log("✅ Cash movement synced:", item.id);

        } catch (err) {

            console.warn("❌ Cash sync failed, will retry:", item.id);

            item.retries = (item.retries || 0) + 1;

            if (item.retries < 5) {
                remaining.push(item);
            } else {
                console.error("🚨 Dropping movement after 5 retries:", item);
            }
        }
    }

    saveCashQueue(remaining);

    window.dispatchEvent(new Event("cashMovementUpdated"));
}

// =============================
// MASTER SYNC
// =============================
async function syncAll() {

    if (!navigator.onLine) return;

    if (isSyncing) {
        console.log("Sync already running, skipping...");
        return;
    }

    isSyncing = true;

    try {

        console.log("🔄 Syncing offline data...");

        await syncSales();
        await syncCash();

        console.log("✅ Offline sync complete");

    } catch (err) {
        console.error("Sync error:", err);
    }

    isSyncing = false;
}

// =============================
// AUTO TRIGGERS
// =============================
window.addEventListener("online", syncAll);

// periodic retry
setInterval(syncAll, 10000);

// =============================
// INIT
// =============================
document.addEventListener("DOMContentLoaded", () => {

    syncAll();

    // notify UI of initial counts
    window.dispatchEvent(new Event("offlineSyncUpdated"));
});