console.log("POS Store loaded");

// =============================
// SINGLE SOURCE OF TRUTH
// =============================
window.POS = window.POS || {};

window.POS.store = {
    register: null,
    cart: [],
    customer: null,

    totals: {
        cash: 0,
        mpesa: 0,
        credit: 0,
        drops: 0,
        expenses: 0,
        payouts: 0,
        deposits: 0,
        adjustments: 0,
        openingCash: 0,
        cashFromCredit: 0
    },

    offlineQueue: {
        sales: [],
        movements: []
    }
};

// =============================
// UPDATE STATE HELPER
// =============================
window.POS.store.update = function (path, value) {

    const keys = path.split(".");
    let obj = this;

    for (let i = 0; i < keys.length - 1; i++) {
        obj = obj[keys[i]];
    }

    obj[keys[keys.length - 1]] = value;

    window.dispatchEvent(new CustomEvent("posStateUpdated", {
        detail: { path, value, state: window.POS.store }
    }));
};