console.log("Customers module loaded");

// =============================
// SAFE NAMESPACE
// =============================
window.POS = window.POS || {};
window.POS.customers = window.POS.customers || {};

// =============================
// STATE
// =============================
let selectedCustomerId = null;

// =============================
// SELECT CUSTOMER
// =============================
window.POS.customers.selectCustomer = function () {

    const select = document.getElementById("customer");
    if (!select) return;

    select.addEventListener("change", function () {

        const option = this.options[this.selectedIndex];

        selectedCustomerId = this.value || null;

        const credit = option?.getAttribute("data-credit") || 0;

        const creditDiv = document.getElementById("customerCreditDiv");
        const creditText = document.getElementById("customerCredit");
        const payBtn = document.getElementById("payCreditBtn");

        if (selectedCustomerId) {

            creditDiv?.classList.remove("hidden");
            payBtn?.classList.remove("hidden");

            if (creditText) {
                creditText.innerText = parseFloat(credit).toFixed(2);
            }

            window.POS.state = window.POS.state || {};
            window.POS.state.customerId = selectedCustomerId;

        } else {

            creditDiv?.classList.add("hidden");
            payBtn?.classList.add("hidden");

            window.POS.state = window.POS.state || {};
            window.POS.state.customerId = null;
        }
    });
};

// =============================
// ADD CUSTOMER TO DROPDOWN (SAFE)
// =============================
function addCustomerToSelect(customer) {

    const select = document.getElementById("customer");
    if (!select || !customer) return;

    const option = document.createElement("option");

    option.value = customer.id;
    option.textContent = `${customer.name} ${customer.phone ? '(' + customer.phone + ')' : ''}`;
    option.setAttribute("data-credit", customer.credit || 0);

    select.appendChild(option);

    select.value = customer.id;
    select.dispatchEvent(new Event("change"));
}

// =============================
// CREATE CUSTOMER MODAL (FIXED STABLE)
// =============================
window.POS.customers.openCreateModal = function () {

    const modal = document.getElementById("customerModal");

    if (modal) {
        modal.classList.remove("hidden");
        return;
    }

    Swal.fire({
        title: "New Customer",
        html: `
            <input id="custName" class="swal2-input" placeholder="Name">
            <input id="custPhone" class="swal2-input" placeholder="Phone">
        `,
        confirmButtonText: "Save",
        showCancelButton: true,
        preConfirm: () => {
            return {
                name: document.getElementById("custName")?.value,
                phone: document.getElementById("custPhone")?.value
            };
        }
    }).then(async (result) => {

        if (!result.value) return;

        try {
            const res = await fetch("/pos/customer-quick-create", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content,
                    "Accept": "application/json",
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: new URLSearchParams(result.value)
            });

            const data = await res.json();

            if (!res.ok) {
                alert(data.message || "Failed to create customer");
                return;
            }

            console.log("Customer created:", data);

            addCustomerToSelect(data);

        } catch (err) {
            console.error("Customer error:", err);
        }
    });
};

// =============================
// RECEIVE CREDIT (FIXED + SINGLE BIND)
// =============================
window.POS.customers.bindReceiveButton = function () {

    const btn = document.getElementById("payCreditBtn");
    if (!btn) return;

    // prevent duplicate binding (CRITICAL FIX)
    if (btn.dataset.bound === "1") return;
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

        try {
            const res = await fetch(`/customers/${customerId}/pay-credit`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content,
                    "Accept": "application/json",
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: new URLSearchParams({
                    amount,
                    method: "Cash",
                    reference: ""
                })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                alert(data.message || "Payment failed");
                return;
            }

            alert("Payment received");

            const option = document.querySelector(`#customer option[value="${customerId}"]`);
            if (option) {
                option.setAttribute("data-credit", data.new_balance);
            }

            document.getElementById("customer").dispatchEvent(new Event("change"));

        } catch (err) {
            console.error(err);
        }
    });
};

// =============================
// NEW CUSTOMER BUTTON (FIXED ROOT ISSUE)
// =============================
window.POS.customers.bindNewCustomerButton = function () {

    const btn = document.getElementById("newCustomerBtn");
    if (!btn) return;

    // prevent duplicate binding (THIS FIXES FLASHING + DOUBLE CLICK BUG)
    if (btn.dataset.bound === "1") return;
    btn.dataset.bound = "1";

    btn.addEventListener("click", (e) => {
        e.preventDefault();
        window.POS.customers.openCreateModal();
    });
};

// =============================
// INIT (SAFE + RE-INIT PROOF)
// =============================
window.POS.customers.init = function () {

    console.log("Customers init running");

    window.POS.customers.selectCustomer();
    window.POS.customers.bindReceiveButton();
    window.POS.customers.bindNewCustomerButton();
};

// =============================
// BOOT (SAFE)
// =============================
document.addEventListener("DOMContentLoaded", () => {
    window.POS.customers.init();
});