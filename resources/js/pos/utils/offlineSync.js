console.log("Offline sync module loaded");

// =============================
// SAFE NAMESPACE
// =============================
window.POS = window.POS || {};

// =============================
// OFFLINE QUEUE KEYS
// =============================
const SALES_QUEUE_KEY = "offline_sales_queue";
const CASH_QUEUE_KEY = "offline_cash_movements";

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
// ADD SALE TO QUEUE
// =============================
window.POS.offlineSync = window.POS.offlineSync || {};

window.POS.offlineSync.queueSale = function (sale) {

    const queue = getSalesQueue();

    queue.push({
        id: Date.now(),
        data: sale,
        synced: false
    });

    saveSalesQueue(queue);

    console.log("Sale queued offline:", sale);
};

// =============================
// ADD CASH MOVEMENT TO QUEUE
// =============================
window.POS.offlineSync.queueCashMovement = function (movement) {

    const queue = getCashQueue();

    queue.push({
        id: Date.now(),
        data: movement,
        synced: false
    });

    saveCashQueue(queue);

    console.log("Cash movement queued offline:", movement);
};

// =============================
// SYNC SALES
// =============================
async function syncSales() {

    let queue = getSalesQueue();
    if (!queue.length) return;

    for (let item of queue) {

        if (item.synced) continue;

        try {

            const res = await fetch("/api/sync-sale", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(item.data)
            });

            const data = await res.json();

            if (data.success) {
                item.synced = true;
            }

        } catch (err) {
            console.error("Sale sync failed:", err);
        }
    }

    queue = queue.filter(i => !i.synced);
    saveSalesQueue(queue);

    window.dispatchEvent(new Event("offlineSyncUpdated"));
}

// =============================
// SYNC CASH MOVEMENTS
// =============================
async function syncCash() {

    let queue = getCashQueue();
    if (!queue.length) return;

    for (let item of queue) {

        if (item.synced) continue;

        try {

            const res = await fetch("/api/cash-movements/sync", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(item.data)
            });

            const data = await res.json();

            if (data.success) {
                item.synced = true;
            }

        } catch (err) {
            console.error("Cash sync failed:", err);
        }
    }

    queue = queue.filter(i => !i.synced);
    saveCashQueue(queue);

    window.dispatchEvent(new Event("cashMovementUpdated"));
}

// =============================
// MASTER SYNC
// =============================
async function syncAll() {

    if (!navigator.onLine) return;

    console.log("Syncing offline data...");

    await syncSales();
    await syncCash();

    console.log("Offline sync complete");
}

// =============================
// AUTO TRIGGERS
// =============================
window.addEventListener("online", syncAll);
setInterval(syncAll, 10000);

// =============================
// INIT SYNC ON LOAD
// =============================
document.addEventListener("DOMContentLoaded", syncAll);