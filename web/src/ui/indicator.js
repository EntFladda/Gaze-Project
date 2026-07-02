/**
 * Focus Status Indicator
 * Visual feedback for focus/unfocus status
 */

class FocusIndicator {
    constructor(elementId = 'focusIndicator') {
        this.element = document.getElementById(elementId);
        this.isFocused = false;
        this.lastStatusChange = Date.now();
    }

    /**
     * Update focus status
     */
    update(isFocused) {
        if (isFocused !== this.isFocused) {
            this.isFocused = isFocused;
            this.lastStatusChange = Date.now();
            this.render();
        }
    }

    /**
     * Render indicator
     */
    render() {
        if (!this.element) return;

        if (this.isFocused) {
            this.element.classList.remove('unfocused', 'idle');
            this.element.classList.add('focused');
            this.element.querySelector('.indicator-label').textContent = '✓ FOCUSED';
        } else {
            this.element.classList.remove('focused', 'idle');
            this.element.classList.add('unfocused');
            this.element.querySelector('.indicator-label').textContent = '⊗ UNFOCUSED';
        }
    }

    /**
     * Get status duration
     */
    getStatusDuration() {
        return (Date.now() - this.lastStatusChange) / 1000;
    }

    /**
     * Set custom color
     */
    setColor(backgroundColor, textColor = 'white') {
        if (this.element) {
            this.element.style.backgroundColor = backgroundColor;
            this.element.style.color = textColor;
        }
    }
}

// Export singleton instance
const focusIndicator = new FocusIndicator();
