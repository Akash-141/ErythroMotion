// Example JavaScript for toggling the menu
document.addEventListener('DOMContentLoaded', function() {
    const toggler = document.querySelector('.navbar-toggler');
    const navLinks = document.querySelector('.navbar-links');

    if (toggler && navLinks) {
        toggler.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }
});