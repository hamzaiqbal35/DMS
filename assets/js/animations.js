// Animation functions
document.addEventListener('DOMContentLoaded', function() {
    // Fade in animation for elements with fade-in class
    const fadeElements = document.querySelectorAll('.fade-in');
    fadeElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transition = 'opacity 0.5s ease-in-out';
        setTimeout(() => {
            element.style.opacity = '1';
        }, 100);
    });

    // Slide in animation for elements with slide-in class
    const slideElements = document.querySelectorAll('.slide-in');
    slideElements.forEach(element => {
        element.style.transform = 'translateY(20px)';
        element.style.opacity = '0';
        element.style.transition = 'all 0.3s ease-in-out';
        setTimeout(() => {
            element.style.transform = 'translateY(0)';
            element.style.opacity = '1';
        }, 100);
    });
});

// Function to add animation classes
function addAnimationClass(element, className) {
    element.classList.add(className);
    // Force reflow
    void element.offsetWidth;
    element.classList.add('animated');
}

// Function to remove animation classes
function removeAnimationClass(element, className) {
    element.classList.remove(className, 'animated');
}

// Modal animation trigger
$(document).on('show.bs.modal', '.modal', function () {
    var dialog = $(this).find('.modal-dialog');
    dialog.removeClass('modal-animate-apply'); // Remove if present
    void dialog[0].offsetWidth; // Force reflow
    dialog.addClass('modal-animate-apply');
});
$(document).on('hidden.bs.modal', '.modal', function () {
    var dialog = $(this).find('.modal-dialog');
    dialog.removeClass('modal-animate-apply');
});

// Export functions if using modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        addAnimationClass,
        removeAnimationClass
    };
} 