console.log("Register module loaded");

// =============================
// SAFE NAMESPACE
// =============================
window.POS = window.POS || {};
window.POS.register = window.POS.register || {};

// =============================
// CSRF HELPER
// =============================
function getCSRF() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
}

// =============================
// OPEN REGISTER
// =============================
window.POS.register.open = async function (openingCash = 0) {

    try {
        const res = await fetch("/register/open", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": getCSRF(),
                "Accept": "application/json",
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                opening_cash: openingCash || 0
            })
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            alert(data.message || "Failed to open register");
            return null;
        }

        window.dispatchEvent(new CustomEvent("registerOpened", { detail: data }));

        return data;

    } catch (err) {
        console.error("Open register error:", err);
    }
};

// =============================
// CLOSE REGISTER (LOGIC ONLY)
// =============================
window.POS.register.close = async function () {

    try {

        const form = document.getElementById('closeRegisterForm');

        if (!form) {
            alert("Close register form not found");
            return;
        }

        const input = document.getElementById('closing_cash');

        let value = 0;

        if (input) {
            value = parseFloat(input.value || "0");
        }

        // SAFE VALIDATION
        if (value < 0 || isNaN(value)) {
            alert("Please enter a valid closing cash amount");
            input?.focus();
            return;
        }

        const formData = new FormData(form);
        formData.set("closing_cash", value);

        const res = await fetch("/register/close", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": getCSRF(),
                "Accept": "application/json"
            },
            body: formData
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            console.error("Close failed:", data);
            alert(data.message || "Failed to close register");
            return null;
        }

        window.dispatchEvent(new CustomEvent("registerClosed", {
            detail: data
        }));

        if (data.redirect) {
            window.location.href = data.redirect;
        }

        return data;

    } catch (err) {
        console.error("Close register error:", err);
        alert("System error closing register");
    }
};

// =============================
// RECEIVE CREDIT PAYMENT (SAFE)
// =============================
window.POS.register.receiveCredit = function () {

    const btn = document.getElementById("payCreditBtn");

    if (!btn || btn.dataset.bound === "1") return;

    btn.dataset.bound = "1";

    btn.addEventListener("click", async () => {

        const customerId = document.getElementById("customer")?.value;

        if (!customerId) {
            alert("Select a customer first");
            return;
        }

        const amount = prompt("Enter amount received:");

        if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
            alert("Invalid amount");
            return;
        }

        const reference = prompt("Mpesa reference (optional)") || "";

        try {
            const res = await fetch(`/customers/${customerId}/pay-credit`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": getCSRF(),
                    "Accept": "application/json",
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: new URLSearchParams({
                    amount,
                    method: "Cash",
                    reference
                })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                alert(data.message || "Payment failed");
                return;
            }

            alert("Payment received successfully");

            window.dispatchEvent(new Event("cashMovementUpdated"));

        } catch (err) {
            console.error("Credit payment error:", err);
        }
    });
};

// =============================
// INIT (NO UI BINDING HERE)
// =============================
window.POS.register.init = function () {

    // ⚠️ DO NOT bind close button here anymore
    // events.js handles UI

    window.POS.register.receiveCredit();
};

// =============================
// SUMMARY
// =============================
window.POS.register.summary = async function (registerId) {

    if (!registerId) return null;

    try {
        const res = await fetch(`/register/${registerId}/totals`, {
            headers: { "Accept": "application/json" }
        });

        return await res.json();

    } catch (err) {
        console.error(err);
        return null;
    }
};

// =============================
// EVENTS
// =============================
window.addEventListener("registerOpened", (e) => {
    console.log("Register opened", e.detail);
});

window.addEventListener("registerClosed", (e) => {
    console.log("Register closed", e.detail);
});

// =============================
// REFRESH TOTALS (🔥 MISSING PIECE)
// =============================
window.POS.register.refreshTotals = async function () {

    const registerId = document.querySelector('[name="register_session_id"]')?.value;

    if (!registerId) {
        console.warn("No register ID found");
        return;
    }

    try {

        const res = await fetch(`/register/${registerId}/totals`, {
            headers: { "Accept": "application/json" }
        });

        const totals = await res.json();

        // 🔥 Broadcast to UI
        window.dispatchEvent(new CustomEvent("registerTotalsUpdated", {
            detail: totals
        }));

    } catch (err) {
        console.error("Failed to refresh totals:", err);
    }
};

