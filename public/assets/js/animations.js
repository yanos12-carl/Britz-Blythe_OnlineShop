document.addEventListener('DOMContentLoaded', function () {
    const sections = document.querySelectorAll('section, .product-card, .account-card');
    sections.forEach(section => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(16px)';
    });
    window.requestAnimationFrame(() => {
        sections.forEach((section, index) => {
            setTimeout(() => {
                section.style.transition = 'opacity 0.45s ease, transform 0.45s ease';
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            }, 60 * index);
        });
    });
});
