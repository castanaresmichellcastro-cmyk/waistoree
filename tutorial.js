// tutorial.js
class AppTutorial {
    constructor() {
        this.currentStep = 0;
        this.steps = [
            {
                element: '.dashboard-stats',
                title: 'Welcome to WAISTORE!',
                content: 'This is your dashboard where you can see your store statistics and quick overview.',
                position: 'bottom'
            },
            {
                element: '.nav-inventory',
                title: 'Inventory Management',
                content: 'Manage your products, add new items, and track your stock levels here.',
                position: 'right'
            },
            {
                element: '.nav-pos',
                title: 'Point of Sale',
                content: 'Process sales, accept payments, and print receipts for your customers.',
                position: 'right'
            },
            {
                element: '.nav-debts',
                title: 'Debt Tracking',
                content: 'Keep track of customer debts and payment schedules.',
                position: 'right'
            },
            {
                element: '.nav-reports',
                title: 'Reports & Analytics',
                content: 'View sales reports, analyze your business performance, and export data.',
                position: 'right'
            }
        ];
        this.init();
    }

    init() {
        this.createOverlay();
        this.showStep(0);
    }

    createOverlay() {
        // Create overlay
        this.overlay = document.createElement('div');
        this.overlay.className = 'tutorial-overlay';
        this.overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9998;
            display: none;
        `;

        // Create tooltip
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'tutorial-tooltip';
        this.tooltip.style.cssText = `
            position: absolute;
            background: white;
            border-radius: 12px;
            padding: 20px;
            max-width: 300px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            z-index: 9999;
            display: none;
        `;

        this.tooltip.innerHTML = `
            <div class="tutorial-header">
                <h3 class="tutorial-title"></h3>
                <button class="tutorial-close">&times;</button>
            </div>
            <div class="tutorial-content"></div>
            <div class="tutorial-footer">
                <div class="tutorial-progress"></div>
                <div class="tutorial-buttons">
                    <button class="tutorial-prev">Previous</button>
                    <button class="tutorial-next">Next</button>
                    <button class="tutorial-finish">Finish</button>
                </div>
            </div>
        `;

        document.body.appendChild(this.overlay);
        document.body.appendChild(this.tooltip);

        // Event listeners
        this.tooltip.querySelector('.tutorial-close').addEventListener('click', () => this.finish());
        this.tooltip.querySelector('.tutorial-prev').addEventListener('click', () => this.previousStep());
        this.tooltip.querySelector('.tutorial-next').addEventListener('click', () => this.nextStep());
        this.tooltip.querySelector('.tutorial-finish').addEventListener('click', () => this.finish());
    }

    showStep(stepIndex) {
        this.currentStep = stepIndex;
        const step = this.steps[stepIndex];
        const targetElement = document.querySelector(step.element);

        if (!targetElement) {
            console.warn('Tutorial element not found:', step.element);
            this.nextStep();
            return;
        }

        // Show overlay and tooltip
        this.overlay.style.display = 'block';
        this.tooltip.style.display = 'block';

        // Position tooltip
        this.positionTooltip(targetElement, step.position);

        // Update content
        this.tooltip.querySelector('.tutorial-title').textContent = step.title;
        this.tooltip.querySelector('.tutorial-content').textContent = step.content;

        // Update progress
        const progress = this.tooltip.querySelector('.tutorial-progress');
        progress.textContent = `${stepIndex + 1} of ${this.steps.length}`;

        // Update buttons
        const prevBtn = this.tooltip.querySelector('.tutorial-prev');
        const nextBtn = this.tooltip.querySelector('.tutorial-next');
        const finishBtn = this.tooltip.querySelector('.tutorial-finish');

        prevBtn.style.display = stepIndex === 0 ? 'none' : 'inline-block';
        nextBtn.style.display = stepIndex === this.steps.length - 1 ? 'none' : 'inline-block';
        finishBtn.style.display = stepIndex === this.steps.length - 1 ? 'inline-block' : 'none';

        // Highlight target element
        this.highlightElement(targetElement);
    }

    positionTooltip(element, position) {
        const rect = element.getBoundingClientRect();
        const tooltipRect = this.tooltip.getBoundingClientRect();

        let top, left;

        switch (position) {
            case 'top':
                top = rect.top - tooltipRect.height - 10;
                left = rect.left + (rect.width - tooltipRect.width) / 2;
                break;
            case 'bottom':
                top = rect.bottom + 10;
                left = rect.left + (rect.width - tooltipRect.width) / 2;
                break;
            case 'left':
                top = rect.top + (rect.height - tooltipRect.height) / 2;
                left = rect.left - tooltipRect.width - 10;
                break;
            case 'right':
                top = rect.top + (rect.height - tooltipRect.height) / 2;
                left = rect.right + 10;
                break;
        }

        // Ensure tooltip stays within viewport
        top = Math.max(10, Math.min(top, window.innerHeight - tooltipRect.height - 10));
        left = Math.max(10, Math.min(left, window.innerWidth - tooltipRect.width - 10));

        this.tooltip.style.top = top + 'px';
        this.tooltip.style.left = left + 'px';
    }

    highlightElement(element) {
        // Remove previous highlights
        document.querySelectorAll('.tutorial-highlight').forEach(el => {
            el.classList.remove('tutorial-highlight');
        });

        // Add highlight to current element
        element.classList.add('tutorial-highlight');
    }

    nextStep() {
        if (this.currentStep < this.steps.length - 1) {
            this.showStep(this.currentStep + 1);
        } else {
            this.finish();
        }
    }

    previousStep() {
        if (this.currentStep > 0) {
            this.showStep(this.currentStep - 1);
        }
    }

    finish() {
        this.overlay.style.display = 'none';
        this.tooltip.style.display = 'none';
        
        // Remove highlights
        document.querySelectorAll('.tutorial-highlight').forEach(el => {
            el.classList.remove('tutorial-highlight');
        });

        // Mark tutorial as completed
        this.markTutorialCompleted();
    }

    markTutorialCompleted() {
        fetch('mark_tutorial_completed.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            console.log('Tutorial completed:', data);
        })
        .catch(error => {
            console.error('Error marking tutorial completed:', error);
        });
    }
}

// Auto-start tutorial if needed
if (typeof showTutorial !== 'undefined' && showTutorial) {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            new AppTutorial();
        }, 1000); // Start after 1 second
    });
}