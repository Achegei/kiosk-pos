console.log("ESC/POS printer module loaded");

// =============================
// ESC/POS PRINT ENGINE
// =============================
window.POS = window.POS || {};
window.POS.printer = window.POS.printer || {};

// =============================
// BASIC TEXT PRINTER (USB / BT READY)
// =============================
window.POS.printer.escposPrint = function (receipt) {

    try {

        const encoder = new TextEncoder();

        const text = `
${receipt.store || "POS STORE"}
------------------------
TOTAL: ${receipt.total || 0}
CASH: ${receipt.cash || 0}
CHANGE: ${receipt.change || 0}
------------------------
THANK YOU
`;

        const data = encoder.encode(text);

        console.log("🧾 ESC/POS data ready:", data);

        // =============================
        // PLACEHOLDER TRANSPORT LAYER
        // =============================
        // You will later connect:
        // - WebUSB printer
        // - Bluetooth printer
        // - Network thermal printer

        if (window.navigator.serial) {
            console.log("⚡ Serial printer supported (WebUSB ready)");
        }

    } catch (err) {
        console.error("ESC/POS print failed:", err);
    }
};