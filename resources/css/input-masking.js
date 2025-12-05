/**
 * Input Masking Utility
 * Formats number inputs with thousand separators
 */

class InputMasking {
    constructor() {
        this.init();
    }

    /**
     * Initialize all amount inputs with masking
     */
    init() {
        // Find all amount inputs
        const amountInputs = document.querySelectorAll('input[type="number"][name*="amount"], input.amount-input');
        amountInputs.forEach(input => {
            this.applyMask(input);
        });
    }

    /**
     * Apply masking to a specific input
     */
    applyMask(input) {
        // Change type to text to allow formatting
        input.setAttribute('type', 'text');
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('pattern', '[0-9,]*');

        // Store the actual numeric value
        let actualValue = input.value ? parseFloat(input.value) || 0 : 0;
        
        // Format initial value if it exists
        if (actualValue) {
            input.value = this.formatNumber(actualValue);
        }

        // Add placeholder if not exists
        if (!input.placeholder) {
            input.placeholder = '0';
        }

        // Handle input event
        input.addEventListener('input', (e) => {
            const cursorPosition = e.target.selectionStart;
            const oldLength = e.target.value.length;

            // Remove all non-numeric characters except dots
            let value = e.target.value.replace(/[^\d]/g, '');
            
            // Convert to number
            actualValue = value ? parseInt(value, 10) : 0;
            
            // Format with thousand separators
            const formattedValue = this.formatNumber(actualValue);
            e.target.value = formattedValue;

            // Restore cursor position
            const newLength = formattedValue.length;
            const lengthDiff = newLength - oldLength;
            const newPosition = cursorPosition + lengthDiff;
            
            e.target.setSelectionRange(newPosition, newPosition);

            // Store actual value in a data attribute
            e.target.dataset.numericValue = actualValue.toString();
        });

        // Handle focus event - select all
        input.addEventListener('focus', (e) => {
            setTimeout(() => {
                e.target.select();
            }, 0);
        });

        // Handle blur event - ensure valid format
        input.addEventListener('blur', (e) => {
            const value = parseInt(e.target.value.replace(/[^\d]/g, ''), 10) || 0;
            e.target.value = this.formatNumber(value);
            e.target.dataset.numericValue = value.toString();
        });

        // Handle form submission - convert back to numeric
        const form = input.closest('form');
        if (form && !form.dataset.maskingHandled) {
            form.dataset.maskingHandled = 'true';
            form.addEventListener('submit', (e) => {
                // Find all masked inputs in this form
                const maskedInputs = form.querySelectorAll('input[data-numeric-value]');
                maskedInputs.forEach(maskedInput => {
                    // Create a hidden input with the numeric value
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = maskedInput.name;
                    hiddenInput.value = maskedInput.dataset.numericValue || '0';
                    
                    // Temporarily disable the formatted input
                    maskedInput.disabled = true;
                    
                    // Add hidden input
                    form.appendChild(hiddenInput);

                    // Re-enable after submission
                    setTimeout(() => {
                        maskedInput.disabled = false;
                        hiddenInput.remove();
                    }, 100);
                });
            });
        }
    }

    /**
     * Format number with thousand separators
     */
    formatNumber(num) {
        if (isNaN(num) || num === null || num === undefined) return '0';
        
        // Convert to number and ensure it's an integer
        const value = Math.floor(Number(num));
        
        // Format with thousand separator (comma)
        return value.toLocaleString('en-US').replace(/,/g, ',');
    }

    /**
     * Parse formatted string to number
     */
    parseNumber(str) {
        if (typeof str !== 'string') return Number(str) || 0;
        return parseInt(str.replace(/[^\d]/g, ''), 10) || 0;
    }

    /**
     * Reinitialize masking (useful for dynamically added inputs)
     */
    reinit() {
        this.init();
    }

    /**
     * Apply masking to a newly added input
     */
    addMaskToInput(input) {
        if (input && input.tagName === 'INPUT') {
            this.applyMask(input);
        }
    }

    /**
     * Get numeric value from a masked input
     */
    getNumericValue(input) {
        return parseInt(input.dataset.numericValue || '0', 10);
    }
}

// Initialize when DOM is ready
let inputMaskingInstance;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        inputMaskingInstance = new InputMasking();
        window.InputMasking = inputMaskingInstance;
    });
} else {
    inputMaskingInstance = new InputMasking();
    window.InputMasking = inputMaskingInstance;
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = InputMasking;
}
