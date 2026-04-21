import Dexie from "dexie";

console.log("IndexedDB (Dexie) initialized");

// =============================
// DATABASE
// =============================
export const db = new Dexie("POS_DB");

// 🔥 VERSIONED SCHEMA (SAFE UPGRADE)
db.version(1).stores({
    products: "id, name, price, stock"
});

db.version(2).stores({
    products: "id, name, price, stock",
    sales: "++id, local_id, synced, created_at",
    receipts: "++id, sale_local_id, created_at",
    cash_movements: "++id, synced, created_at"
}).upgrade(tx => {
    console.log("Upgrading DB to v2...");

    return tx.table("sales").toCollection().modify(sale => {
        if (!sale.local_id) {
            sale.local_id = "sale_" + Date.now();
        }
        if (sale.synced === undefined) {
            sale.synced = false;
        }
        if (!sale.created_at) {
            sale.created_at = new Date().toISOString();
        }
    });
});

// =============================
// GLOBAL ACCESS
// =============================
window.POS = window.POS || {};

window.POS.db = {

    // =============================
    // PRODUCTS
    // =============================
    async saveProducts(products) {
        if (!Array.isArray(products)) return;

        await db.products.clear(); // 🔥 prevent stale stock
        await db.products.bulkPut(products);

        console.log("✅ Products cached:", products.length);
    },

    async searchProducts(query) {

        if (!query) return [];

        query = query.toLowerCase();

        return await db.products
            .filter(p => p.name?.toLowerCase().includes(query))
            .limit(20)
            .toArray();
    },

    // =============================
    // SALES (CRITICAL)
    // =============================
    async saveSale(sale) {

        const local_id = sale.local_id || ("sale_" + Date.now());

        await db.sales.add({
            ...sale,
            local_id,
            synced: false,
            created_at: new Date().toISOString()
        });

        console.log("💾 Sale saved locally:", local_id);

        return local_id;
    },

    async getPendingSales() {
        return await db.sales.where("synced").equals(0).toArray();
    },

    async markSaleSynced(local_id) {

        await db.sales
            .where("local_id")
            .equals(local_id)
            .modify({ synced: true });

        console.log("✅ Sale marked synced:", local_id);
    },

    // =============================
    // RECEIPTS (🔥 FIX YOUR PRINT ISSUE)
    // =============================
    async saveReceipt(receipt) {

        if (!receipt?.sale_local_id) return;

        await db.receipts.add({
            ...receipt,
            created_at: new Date().toISOString()
        });

        console.log("🧾 Receipt saved locally");
    },

    async getReceiptBySale(local_id) {

        return await db.receipts
            .where("sale_local_id")
            .equals(local_id)
            .first();
    },

    // =============================
    // CASH MOVEMENTS
    // =============================
    async saveCashMovement(movement) {

        await db.cash_movements.add({
            ...movement,
            synced: false,
            created_at: new Date().toISOString()
        });

        console.log("💰 Cash movement saved locally");
    },

    async getPendingCashMovements() {
        return await db.cash_movements.where("synced").equals(0).toArray();
    },

    async markCashSynced(id) {
        await db.cash_movements.update(id, { synced: true });
    }

};