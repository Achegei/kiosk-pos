console.log("Checkout module initialized");

// =============================
// HELPERS
// =============================
function getSubtotal() {
    return parseFloat(document.getElementById('subtotal')?.innerText || 0);
}

function getCash() {
    return parseFloat(document.getElementById('cashGiven')?.value || 0);
}

function getPaymentMethod() {
    return document.getElementById('payment_method')?.value;
}

function getCustomer() {
    return document.getElementById('customer')?.value;
}

function getCart() {
    return window.POS?.state?.getCart?.() || [];
}

// =============================
// VALIDATION
// =============================
function validateSale(subtotal, method, cash) {

    if (!getCart().length) {
        Swal.fire('Cart empty', '', 'warning');
        return false;
    }

    if (method === "Cash" && cash < subtotal) {
        Swal.fire({
            icon: 'error',
            title: 'Insufficient Cash',
            text: 'Customer must pay full amount'
        });
        return false;
    }

    if (method === "Credit" && !getCustomer()) {
        Swal.fire('Select customer', 'Credit sales require customer', 'error');
        return false;
    }

    return true;
}

// =============================
// MPESA HANDLER
// =============================
async function handleMpesa(subtotal) {

    const result = await Swal.fire({
        title: 'Enter Mpesa Code',
        input: 'text',
        inputPlaceholder: 'Example: QWE123ABC',
        confirmButtonText: 'Confirm',
        showCancelButton: true,
        inputAttributes: {
            maxlength: 10,
            autocapitalize: 'characters'
        },
        inputValidator: (value) => {
            if (!value) return 'Mpesa code required';
            if (value.length > 10) return 'Max 10 characters';
        }
    });

    if (!result.value) return null;

    return {
        mpesaRef: result.value,
        cashGiven: subtotal
    };
}

// =============================
// SUBMIT SALE
// =============================
async function submitSale(payload, form) {

    const res = await fetch(form.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    return await res.json();
}

// =============================
// PRINT RECEIPT
// =============================
function printReceipt(receipt) {
    if (window.printReceipt) {
        window.printReceipt(receipt);
    }
}

// =============================
// FINAL CHECKOUT
// =============================
document.getElementById('checkoutForm')?.addEventListener('submit', async function (e) {

    e.preventDefault();

    if (window.POS.state.isCheckoutLocked()) {
        Swal.fire('Processing...', 'Wait for current sale', 'info');
        return;
    }

    window.POS.state.lockCheckout();

    const method = getPaymentMethod();
    const subtotal = getSubtotal();
    let cash = getCash();
    let mpesaRef = null;

    try {

        // ================= VALIDATE =================
        if (!validateSale(subtotal, method, cash)) {
            return;
        }

        // ================= MPESA =================
        if (method === "Mpesa") {
            const mpesa = await handleMpesa(subtotal);
            if (!mpesa) return;

            mpesaRef = mpesa.mpesaRef;
            cash = mpesa.cashGiven;
        }

        // ================= CUSTOMER =================
        const selectedCustomer = document.getElementById('customer');
        const customerName = selectedCustomer?.selectedOptions[0]?.text || 'Walk-in';
        const creditBefore = parseFloat(selectedCustomer?.selectedOptions[0]?.dataset.credit || 0);

        let creditChange = 0;
        let creditAfter = creditBefore;

        if (method === "Credit") {
            creditChange = subtotal;
            creditAfter = creditBefore + creditChange;
        }

        // ================= PAYLOAD =================
        const payload = {
            _token: document.querySelector('input[name=_token]')?.value,
            customer_id: getCustomer(),
            payment_method: method,
            mpesa_code: mpesaRef,
            products: getCart().map(p => ({
                id: p.id,
                quantity: p.quantity
            })),
            subtotal,

            credit_before: creditBefore,
            credit_change: creditChange,
            credit_after: creditAfter,
            customer_name: customerName
        };

        // ================= ONLINE =================
        if (navigator.onLine) {

            const data = await submitSale(payload, this);

            if (!data.success) {
                throw new Error("Server rejected sale");
            }

            window.dispatchEvent(new Event('transactionCompleted'));

            const receipt = {
                ...data.receipt,
                payment_method: method,
                mpesa_reference: mpesaRef,
                cash,
                change: Math.max(0, cash - subtotal)
            };

            printReceipt(receipt);

            Swal.fire({
                icon: 'success',
                title: 'SALE COMPLETED',
                timer: 1500,
                showConfirmButton: false
            });

        } else {

            // ================= OFFLINE QUEUE =================
            window.POS.offlineSync.queueSale({
                ...payload,
                local_id: 'sale_' + Date.now(),
                timestamp: new Date().toISOString(),
                device_uuid: window.POS.device?.getId?.() || 'unknown'
            });

            printReceipt({
                ...payload,
                offline: true,
                cash,
                change: Math.max(0, cash - subtotal)
            });

            Swal.fire('Offline Sale', 'Saved locally & will sync automatically', 'info');

            window.dispatchEvent(new Event('offlineSyncUpdated'));
        }

        // ================= CLEANUP =================
        window.POS.state.clearCart();
        window.POS?.cart?.render?.();
        document.getElementById('cashGiven').value = '';

    } catch (err) {

        console.error("Checkout error:", err);

        Swal.fire('Error', 'Sale failed or saved offline', 'warning');

        window.POS.offlineSync.queueSale({
            ...payload,
            local_id: 'sale_' + Date.now(),
            timestamp: new Date().toISOString(),
            device_uuid: window.POS.device?.getId?.() || 'unknown'
        });

    } finally {

        window.POS.state.unlockCheckout();
    }
});