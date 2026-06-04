// Main JS file for login/register pages
document.addEventListener('DOMContentLoaded', function() {
    // Add any global functionality here
    console.log('ADSSU LAMS Loaded');
});

// Form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('input[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#ef4444';
            isValid = false;
        } else {
            input.style.borderColor = '#334155';
        }
    });
    
    return isValid;
}

// Show loading state on buttons
function showLoading(button, text = 'Loading...') {
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + text;
    return originalText;
}

function hideLoading(button, originalText) {
    button.disabled = false;
    button.innerHTML = originalText;
}