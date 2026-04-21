console.log("UI Events module loaded");

// =============================
// SAFE GLOBAL NAMESPACE
// =============================
window.POS = window.POS || {};

// =============================
// LOCK SYSTEM (PREVENT DOUBLE BINDS)
// =============================
window.POS.locks = window.POS.locks || {
    ui: false
};

// =============================
// SAFE EVENT DISPATCH
// =============================
function safeDispatch(eventName, detail = {}) {
    window.dispatchEvent(new CustomEvent(eventName, { detail }));
}

// =============================
// INIT HELPER
// =============================
function onReady(fn) {
    if (document.readyState !== "loading") fn();
    else document.addEventListener("DOMContentLoaded", fn);
}

// =============================
// UI BINDINGS (ONLY PLACE FOR BUTTONS)
// =============================
onReady(() => {

    // -----------------------------
// OPEN REGISTER FORM
// -----------------------------
const openForm = document.getElementById('openRegisterForm');

if (openForm && openForm.dataset.bound !== "1") {

    openForm.dataset.bound = "1";

    openForm.addEventListener('submit', async function (e) {

        e.preventDefault();

        console.log("Opening register...");

        const input = document.getElementById('openingCashInput');

        const value = parseFloat(input?.value || "0");

        if (isNaN(value) || value < 0) {
            alert("Enter valid opening cash");
            input?.focus();
            return;
        }

        const result = await window.POS.register?.open?.(value);

        if (result) {
            // reload POS to reflect open register state
            window.location.reload();
        }
    });
}

    // -----------------------------
    // CLOSE REGISTER BUTTON
    // -----------------------------
    const closeBtn = document.getElementById('triggerCloseRegisterBtn');

if (closeBtn && closeBtn.dataset.bound !== "1") {

    closeBtn.dataset.bound = "1";

    closeBtn.addEventListener('click', async () => {

        console.log("Close register button clicked");

        const modal = document.getElementById('closeRegisterModal');

        if (modal) {
            modal.classList.remove('hidden');

            // 🔥 THIS is the missing link
            await window.POS.register.refreshTotals();
        }
    });
}

    // -----------------------------
// CLOSE REGISTER CANCEL BUTTON (FIXED)
// -----------------------------
const cancelBtn = document.getElementById('cancelCloseRegister');

if (cancelBtn && cancelBtn.dataset.bound !== "1") {

    cancelBtn.dataset.bound = "1";

    cancelBtn.addEventListener('click', (e) => {

        e.preventDefault();

        console.log("Close register cancelled");

        const modal = document.getElementById('closeRegisterModal');

        // OPTION A: just close modal
        if (modal) {
            modal.classList.add('hidden');
            return;
        }

        // fallback (if modal missing)
        window.location.href = "/dashboard";
    });
} 
    // -----------------------------
    // SUBMIT CLOSE REGISTER FORM
    // -----------------------------
    const closeForm = document.getElementById('closeRegisterForm');

    if (closeForm && closeForm.dataset.bound !== "1") {

        closeForm.dataset.bound = "1";

        closeForm.addEventListener('submit', function (e) {

            e.preventDefault();

            console.log("Submitting close register form");

            window.POS.register?.close?.();
        });
    }

    // -----------------------------
    // CASH MOVEMENT BUTTON
    // -----------------------------
    const cashBtn = document.getElementById('triggerCashMovementBtn');

    if (cashBtn && cashBtn.dataset.bound !== "1") {

        cashBtn.dataset.bound = "1";

        cashBtn.addEventListener('click', () => {
            console.log("Cash movement clicked");
            window.POS.cash?.openMovementModal?.();
        });
    }

    // -----------------------------
    // REFRESH BUTTON
    // -----------------------------
    const refreshBtn = document.getElementById('refreshRegisterBtn');

    if (refreshBtn && refreshBtn.dataset.bound !== "1") {

        refreshBtn.dataset.bound = "1";

        refreshBtn.addEventListener('click', async () => {

            console.log("Manual refresh triggered");

            if (window.POS.locks.ui) return;

            window.POS.locks.ui = true;

            try {
                await window.POS.cash?.refresh?.();
                await window.POS.register?.summary?.();

                safeDispatch("posUIRefresh");

            } finally {
                setTimeout(() => {
                    window.POS.locks.ui = false;
                }, 150);
            }
        });
    }

});

// =============================
// GLOBAL EVENTS
// =============================
window.addEventListener('transactionCompleted', async () => {

    console.log("Transaction completed → UI refresh");

    await window.POS.cash?.refresh?.();

    safeDispatch("posUIRefresh");
});

window.addEventListener("cashMovementUpdated", async () => {

    console.log("Cash movement → UI refresh");

    await window.POS.cash?.refresh?.();

    safeDispatch("posUIRefresh");
});

window.addEventListener("offlineSyncUpdated", async () => {

    console.log("Offline sync → UI refresh");

    await window.POS.cash?.refresh?.();

    safeDispatch("posUIRefresh");
});

// =============================
// POS READY → UI REGISTERS LISTENERS
// =============================
window.addEventListener("posReady", () => {

    console.log("Register UI listener ready");

    // Prevent double binding
    if (window.POS.locks.registerTotals) return;
    window.POS.locks.registerTotals = true;

    window.addEventListener("registerTotalsUpdated", (e) => {

        const totals = e.detail;
        if (!totals) return;

        const set = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = parseFloat(val || 0).toFixed(2);
        };

        // =========================
        // CORE TOTALS
        // =========================
        set('cashTotal', totals.cash);
        set('mpesaTotal', totals.mpesa);
        set('creditTotal', totals.credit);
        set('cashFromCredit', totals.cashFromCredit);

        // =========================
        // TILL MOVEMENTS
        // =========================
        set('dropTotal', totals.drops);
        set('expenseTotal', totals.expenses);
        set('payoutTotal', totals.payouts);
        set('depositTotal', totals.deposits);
        set('adjustTotal', totals.adjustments);

        // =========================
        // EXPECTED CASH CALCULATION
        // =========================
        const expectedCash =
            parseFloat(totals.openingCash || 0)
            + parseFloat(totals.cash || 0)
            + parseFloat(totals.cashFromCredit || 0)
            - parseFloat(totals.drops || 0)
            - parseFloat(totals.expenses || 0)
            - parseFloat(totals.payouts || 0)
            + parseFloat(totals.deposits || 0)
            + parseFloat(totals.adjustments || 0);

        set('expectedCash', expectedCash);

        // =========================
        // CASH + MPESA TOTALS
        // =========================
        const totalCashMpesa =
            parseFloat(totals.cash || 0) +
            parseFloat(totals.mpesa || 0);

        set('totalCashMpesa', totalCashMpesa);

        set(
            'grandTotalCash',
            parseFloat(totals.openingCash || 0) + totalCashMpesa
        );
    });

});

window.addEventListener("offlineSyncUpdated", () => {

    const queue = JSON.parse(localStorage.getItem("offline_sales_queue") || "[]");

    const banner = document.getElementById("offlineBanner");

    if (!banner) return;

    if (queue.length > 0) {
        banner.classList.remove("hidden");
        banner.innerText = `⚠ ${queue.length} sales pending sync`;
    } else {
        banner.classList.add("hidden");
    }
});