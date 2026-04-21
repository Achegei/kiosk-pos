import Dexie from "dexie";

console.log("IndexedDB (Dexie) initialized");

export const db = new Dexie("POS_DB");

db.version(1).stores({
    products: "id, name, price, stock"
});

window.POS = window.POS || {};

window.POS.db = {

    async saveProducts(products) {
        if (!Array.isArray(products)) return;

        await db.products.bulkPut(products);
        console.log("Products saved:", products.length);
    },

    async searchProducts(query) {

        if (!query) return [];

        query = query.toLowerCase();

        return await db.products
            .filter(p => p.name?.toLowerCase().includes(query))
            .limit(20)
            .toArray();
    }

};