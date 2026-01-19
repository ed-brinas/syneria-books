/*
 * Global Spinner & UI Logic
 * Handles loading states for Livewire navigations and standard page loads.
 * Location: public/js/app.js
 */

// Helper to safely hide spinner
function hideSpinner() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        spinner.style.display = 'none';
    }
}

// 1. Initial Page Load Handlers
window.addEventListener('load', hideSpinner);

// If page is already loaded by the time this script runs
if (document.readyState === 'complete') {
    hideSpinner();
}

// Failsafe: Force hide after 3 seconds to prevent "stuck" state
setTimeout(hideSpinner, 3000);

document.addEventListener('DOMContentLoaded', () => {
    
    const spinner = document.getElementById('loading-spinner');

    // 2. Livewire 3 Lifecycle Hooks
    // Show spinner when Livewire makes a request (navigation or action)
    document.addEventListener('livewire:navigating', () => {
        if(spinner) spinner.style.display = 'flex';
    });

    // Hide spinner when navigation finishes
    document.addEventListener('livewire:navigated', () => {
        if(spinner) spinner.style.display = 'none';
    });
    
    // Core Livewire Request Handling
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('request', ({ fail, respond }) => {
            // Show spinner on start
            if(spinner) spinner.style.display = 'flex';
    
            // Hide on success/response
            respond(() => {
                if(spinner) spinner.style.display = 'none';
            });

            // Hide on failure
            fail(() => {
                if(spinner) spinner.style.display = 'none';
            });
        });
    }

    // 3. Initialize Bootstrap Tooltips/Popovers globally
    // Ensure bootstrap global is available (loaded via CDN or script tag in layout)
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }
});