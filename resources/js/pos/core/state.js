console.log("Core state module loaded");

// =============================
// GLOBAL POS NAMESPACE
// =============================
window.POS = window.POS || {};

// =============================
// SINGLE SOURCE OF TRUTH (CART)
// =============================
window.cart = window.cart || [];

// =============================
// CORE STATE ENGINE
// =============================
window.POS.state = {

    // cart synced with global
    cart: window.cart,

    // checkout lock
    checkoutPending: false,

    // customer
    customerId: null,

    // payment
    cashGiven: 0,
    paymentMethod: 'Cash',
    mpesaRef: null,

    // =============================
    // CREDIT ENGINE (FIX FOR YOUR ISSUE)
    // =============================
    credit: {
        before: 0,
        change: 0,
        after: 0,

        setBefore(value) {
            this.before = Number(value || 0);
        },

        applyChange(amount) {
            this.change = Number(amount || 0);
            this.after = this.before + this.change;
        },

        reset() {
            this.before = 0;
            this.change = 0;
            this.after = 0;
        }
    },

    // =============================
    // CART SYNC HELPERS
    // =============================
    setCart(cart) {
        window.cart = cart;
        this.cart = cart;
    },

    getCart() {
        return window.cart || this.cart || [];
    },

    clearCart() {
        window.cart = [];
        this.cart = [];
    },

    // =============================
    // CHECKOUT LOCK HELPERS
    // =============================
    lockCheckout() {
        this.checkoutPending = true;
    },

    unlockCheckout() {
        this.checkoutPending = false;
    },

    isCheckoutLocked() {
        return this.checkoutPending;
    },

    // =============================
    // PAYMENT HELPERS (OPTIONAL BUT USEFUL)
    // =============================
    setPayment(method, cash = 0, mpesaRef = null) {
        this.paymentMethod = method;
        this.cashGiven = cash;
        this.mpesaRef = mpesaRef;
    },

    // =============================
    // RESET STATE AFTER SALE
    // =============================
    reset() {
        this.clearCart();
        this.customerId = null;
        this.cashGiven = 0;
        this.paymentMethod = 'Cash';
        this.mpesaRef = null;
        this.unlockCheckout();
        this.credit.reset();
    }
};