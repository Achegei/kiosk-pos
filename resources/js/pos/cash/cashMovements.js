console.log("Cash movements module loaded");

// =============================
// SAFE NAMESPACE
// =============================
window.POS = window.POS || {};
window.POS.cash = window.POS.cash || {};

// =============================
// CSRF HELPER
// =============================
function getCSRF() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
}

// =============================
// ADD CASH MOVEMENT (FIXED ROUTE + SAFETY)
// =============================
async function addMovement(type, amount, note = '', registerSessionId = null) {

    if (!registerSessionId) {
        console.error("Missing register session ID");
        alert("Register session not found");
        return null;
    }

    try {
        const res = await fetch("/cash-movements/store", {   // ✅ FIXED ROUTE
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": getCSRF(),
                "Accept": "application/json",
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                type,
                amount: Number(amount || 0),
                note: note || '',
                register_session_id: registerSessionId
            })
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            console.error("Cash movement failed:", data);
            alert(data.message || "Failed to save cash movement");
            return null;
        }

        console.log("Cash movement saved:", data);

        window.dispatchEvent(new Event("cashMovementUpdated"));

        return data;

    } catch (err) {
        console.error("Cash movement error:", err);
    }
}

// =============================
// OPEN MODAL (HARDENED)
// =============================
window.POS.cash.openMovementModal = function () {

    console.log("Cash movement modal opened");

    Swal.fire({
        title: 'Cash Movement',
        html: `
            <select id="cashType" class="swal2-input">
                <option value="drop">Cash Drop</option>
                <option value="expense">Expense</option>
                <option value="payout">Payout</option>
                <option value="deposit">Deposit</option>
                <option value="adjustment">Adjustment</option>
            </select>

            <input id="cashAmount" type="number" class="swal2-input" placeholder="Amount">
            <input id="cashNote" type="text" class="swal2-input" placeholder="Note">
        `,
        confirmButtonText: 'Save',
        showCancelButton: true,
        focusConfirm: false,

        preConfirm: () => {

            const type = document.getElementById('cashType')?.value;
            const amount = document.getElementById('cashAmount')?.value;
            const note = document.getElementById('cashNote')?.value;

            if (!type) {
                Swal.showValidationMessage("Select movement type");
                return false;
            }

            if (!amount || Number(amount) <= 0) {
                Swal.showValidationMessage("Enter valid amount");
                return false;
            }

            return { type, amount, note };
        }
    }).then(async result => {

        if (!result.value) return;

        const registerSessionId =
            document.querySelector('input[name="register_session_id"]')?.value ||
            document.querySelector('#checkoutForm input[name="register_session_id"]')?.value;

        await addMovement(
            result.value.type,
            result.value.amount,
            result.value.note,
            registerSessionId
        );
    });
};

// =============================
// FETCH CASH SUMMARY
// =============================
window.POS.cash.refresh = async function (registerSessionId) {

    if (!registerSessionId) return null;

    try {
        const res = await fetch(`/cash-movements/summary/${registerSessionId}`, {
            headers: {
                "Accept": "application/json"
            }
        });

        const data = await res.json();

        console.log("Cash summary:", data);

        return data;

    } catch (err) {
        console.error("Cash summary error:", err);
        return null;
    }
};

// =============================
// PUBLIC API
// =============================
window.POS.cash.add = addMovement;