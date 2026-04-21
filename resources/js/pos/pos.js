console.log("POS system booting...");

// =============================
// CORE (LOAD FIRST)
// =============================
import "./core/state";
import "./core/device";
import "./core/network";
import "./core/db";

// =============================
// CORE FEATURES
// =============================
import "./cart/cart";
import "./products/search";
import "./customers/customers";
import "./checkout/checkout";

// =============================
// FINANCIAL MODULES
// =============================
import "./cash/cashMovements";
import "./register/register";

// =============================
// UI + SYSTEM EVENTS (IMPORTANT FIX)
// =============================
import "./ui/events";
import "./ui/receipt";
import "./ui/calculator";

// =============================
// PRINTER MODULES (NEW)
// =============================
import "./printer/escpos";

// =============================
// UTILITIES (MISSING BEFORE)
// =============================
import "./utils/offlineSync";

// =============================
// SYSTEM READY EVENT
// =============================
document.addEventListener("DOMContentLoaded", () => {

    console.log("POS system fully initialized");

    window.dispatchEvent(new Event("posReady"));

});

// =============================
// 🔥 SERVICE WORKER REGISTRATION (CRITICAL)
// =============================
if ('serviceWorker' in navigator) {

    window.addEventListener('load', () => {

        navigator.serviceWorker.register('/sw.js')
            .then(reg => {
                console.log("✅ Service Worker registered:", reg.scope);
            })
            .catch(err => {
                console.error("❌ Service Worker registration failed:", err);
            });

    });
}