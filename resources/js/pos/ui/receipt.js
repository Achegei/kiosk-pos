console.log("Receipt module loaded");

// =============================
// SAFE GLOBAL NAMESPACE
// =============================
window.POS = window.POS || {};
window.POS.receipt = window.POS.receipt || {};

// =============================
// MAIN RENDER FUNCTION
// =============================
function renderReceipt(receipt = {}) {

    console.log("Receipt module invoked:", receipt);

    const storeInfo = window.STORE || {
        name: "POS STORE",
        address: "",
        phone: "",
        footer: "Thank you for your purchase"
    };

    const staffName = receipt.user || window.currentUserName || "Staff";

    const cash = Number(receipt.cash || 0);
    const change = Number(receipt.change || 0);
    const total = Number(receipt.total || 0);

    // =============================
    // ITEMS
    // =============================
    let itemsHtml = "";

    (receipt.items || []).forEach(item => {

        const qty = Number(item.qty || item.quantity || 0);
        const price = Number(item.price || 0);
        const lineTotal = Number(item.total || (qty * price));

        itemsHtml += `
            <div class="row">
                <div>${item.name || 'Item'}</div>
                <div>${qty} x ${price.toFixed(2)}</div>
            </div>
            <div class="row total">
                <div></div>
                <div>KES ${lineTotal.toFixed(2)}</div>
            </div>
        `;
    });

    // =============================
    // 🔥 FIXED CREDIT MAPPING
    // =============================
    const creditBefore = Number(receipt.customer?.previous_credit || 0);
    const creditChange = Number(receipt.customer?.credit_added || 0);
    const creditAfter  = Number(receipt.customer?.total_credit || 0);

    // =============================
    // PAYMENT SECTION
    // =============================
    let paymentSection = "";

    switch (receipt.payment_method) {

        case "Mpesa":
            paymentSection = `
                <strong>Paid via: MPESA</strong><br>
                Ref: ${receipt.mpesa_reference || 'N/A'}<br>
                Cash: KES ${cash.toFixed(2)}<br>
                Change: KES ${change.toFixed(2)}
            `;
            break;

        case "Credit":
            paymentSection = `
                <strong>Paid via: CREDIT</strong><br>
                Customer: ${receipt.customer?.name || 'N/A'}<br>
                Credit Before: KES ${creditBefore.toFixed(2)}<br>
                Credit Change: +${creditChange.toFixed(2)}<br>
                <strong>Credit After: KES ${creditAfter.toFixed(2)}</strong>
            `;
            break;

        default:
            paymentSection = `
                <strong>Paid via: CASH</strong><br>
                Cash: KES ${cash.toFixed(2)}<br>
                Change: KES ${change.toFixed(2)}
            `;
    }

    // =============================
    // TEMPLATE
    // =============================
    const html = `
    <html>
    <head>
        <title>Receipt</title>
        <style>
            body {
                font-family: monospace;
                width: 80mm;
                margin: auto;
                padding: 10px;
            }
            .center { text-align: center; }
            .row {
                display: flex;
                justify-content: space-between;
                font-size: 13px;
            }
            .total { font-weight: bold; }
            hr { border-top: 1px dashed #000; }
            .big {
                font-size: 18px;
                font-weight: bold;
                text-align: center;
                margin-top: 10px;
            }
        </style>
    </head>

    <body>

        <div class="center">
            <div style="font-size:18px;font-weight:bold">${storeInfo.name}</div>
            ${storeInfo.address || ''}<br>
            ${storeInfo.phone || ''}
        </div>

        <hr>

        Receipt #: ${receipt.receipt_number || 'N/A'}<br>
        Date: ${new Date().toLocaleString()}<br>
        Served by: ${staffName}

        <hr>

        ${itemsHtml || '<div>No items</div>'}

        <hr>

        <div class="big">
            TOTAL: KES ${total.toFixed(2)}
        </div>

        <hr>

        ${paymentSection}

        <hr>

        <div class="center">
            ${storeInfo.footer}
        </div>

    </body>
    </html>
    `;

    // =============================
    // PRINT
    // =============================
    const win = window.open('', '_blank', 'width=350,height=700');

    if (!win) {
        alert("Enable popups to print receipt");
        return;
    }

    win.document.write(html);
    win.document.close();

    setTimeout(() => {
        win.focus();
        win.print();
    }, 300);
}

// =============================
window.POS.receipt.print = renderReceipt;
window.printReceipt = renderReceipt;