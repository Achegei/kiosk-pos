console.log("POS system booting...");

// =============================
// 🔥 SAFETY FIRST (CRITICAL)
// =============================
import "../bootstrap";           // axios (now intercepted)
import "./core/bootstrap";       // crash guard (YOU created)

// =============================
// CORE SYSTEM (SAFE NOW)
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
// UI + SYSTEM EVENTS
// =============================
import "./ui/events";
import "./ui/receipt";
import "./ui/calculator";

// =============================
// PRINTER MODULES
// =============================
import "./printer/escpos";

// =============================
// UTILITIES
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
// 🔥 SERVICE WORKER REGISTRATION
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