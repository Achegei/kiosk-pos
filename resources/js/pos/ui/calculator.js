console.log("Calculator module loaded");

// =============================
// SAFE NAMESPACE
// =============================
window.POS = window.POS || {};

// =============================
// CALCULATOR STATE (ISOLATED)
// =============================
window.POS.calculator = {
    expression: "",
    lastResult: 0
};

// =============================
// DOM READY
// =============================
document.addEventListener("DOMContentLoaded", () => {

    const input = document.getElementById("quickCalcInput");

    if (!input) return;

    // Prevent ANY focus stealing from calculator
    input.addEventListener("focus", (e) => {
        e.preventDefault();
    });

    function updateDisplay() {
        input.value = window.POS.calculator.expression || "0";
    }

    function safeSyncToCash(value) {

        const amount = Number(value || 0);

        // ONLY sync state (no DOM focus)
        if (window.POS?.state) {
            window.POS.state.calculatorValue = amount;
        }

        // Dispatch clean event ONLY
        window.dispatchEvent(new CustomEvent("calculatorUpdated", {
            detail: { value: amount }
        }));
    }

    document.querySelectorAll(".calcBtn").forEach(btn => {

        btn.addEventListener("click", (e) => {

            const val = btn.innerText;

            if (val === "C") {
                window.POS.calculator.expression = "";
                updateDisplay();
                return;
            }

            if (val === "=") {
                try {
                    const result = Function(`return ${window.POS.calculator.expression || 0}`)();

                    window.POS.calculator.expression = String(result || 0);
                    window.POS.calculator.lastResult = result;

                    updateDisplay();

                    // IMPORTANT: only sync here
                    safeSyncToCash(result);

                } catch (err) {
                    console.error("Calculator error:", err);
                    window.POS.calculator.expression = "0";
                    updateDisplay();
                }

                return;
            }

            window.POS.calculator.expression += val;
            updateDisplay();
        });
    });

    updateDisplay();
});