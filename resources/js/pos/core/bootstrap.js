console.log("POS Bootstrap starting...");

// =============================
// GLOBAL POS OBJECT
// =============================
window.POS = window.POS || {};

// =============================
// BOOT STATE
// =============================
window.POS.boot = {
    ready: false,
    offline: !navigator.onLine,
    errors: []
};

// =============================
// GLOBAL ERROR HANDLER (PREVENT CRASH)
// =============================
window.addEventListener("error", (e) => {

    console.error("🚨 Global JS Error:", e.message);

    window.POS.boot.errors.push(e.message);

    // prevent white screen crash
    e.preventDefault();
});

// =============================
// PROMISE ERROR HANDLER
// =============================
window.addEventListener("unhandledrejection", (e) => {

    console.error("🚨 Promise Error:", e.reason);

    window.POS.boot.errors.push(e.reason);

    e.preventDefault();
});

// =============================
// SAFE FETCH (USE EVERYWHERE)
// =============================
window.POS.safeFetch = async function (url, options = {}) {

    if (!navigator.onLine) {
        console.warn("📴 Offline → blocked request:", url);
        throw new Error("Offline");
    }

    try {
        return await fetch(url, options);
    } catch (err) {
        console.warn("🌐 Network failed:", url);
        throw err;
    }
};

// =============================
// ONLINE / OFFLINE TRACKING
// =============================
window.addEventListener("online", () => {
    console.log("🟢 Back online");
    window.POS.boot.offline = false;
});

window.addEventListener("offline", () => {
    console.log("🔴 Offline mode");
    window.POS.boot.offline = true;
});

// =============================
// BOOT READY
// =============================
window.POS.boot.ready = true;

console.log("✅ POS Bootstrap ready");