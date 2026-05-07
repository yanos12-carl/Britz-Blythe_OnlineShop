document.addEventListener('DOMContentLoaded', function () {
    const html = document.documentElement;
    const toggle = document.querySelector('[data-theme-toggle]');
    const body = document.body;

    // Load saved theme or system preference
    const saved = localStorage.getItem('britz-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    let current = saved || (prefersDark ? 'dark' : 'light');

    html.dataset.theme = current;
    if (toggle) {
        toggle.innerHTML = current === 'dark' ? '☀️' : '🌙';
        toggle.classList.add('theme-toggle-btn');
    }
    body.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';

    // Toggle handler
    if (toggle) {
        toggle.addEventListener('click', function () {
            const next = current === 'dark' ? 'light' : 'dark';
            html.dataset.theme = next;

            toggle.classList.add('rotating'); // Add rotating class
            current = next;
            localStorage.setItem('britz-theme', next);
            toggle.innerHTML = next === 'dark' ? '☀️' : '🌙';

            setTimeout(() => toggle.classList.remove('rotating'), 500); // Remove after animation

            // Smooth backdrop transition
            body.style.backdropFilter = 'blur(20px)';
            setTimeout(() => {
                body.style.backdropFilter = '';
            }, 500);
        });
    }

    // Listen for system changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!localStorage.getItem('britz-theme')) {
            const theme = e.matches ? 'dark' : 'light';
            html.dataset.theme = theme;
            current = theme;

            const btn = document.querySelector('[data-theme-toggle]');
            if (btn) {
                btn.innerHTML = theme === 'dark' ? '☀️' : '🌙';
                // No rotation on system preference change, only on manual click
            }
        }
    });

    // Search functionality
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.target.form.submit();
            }
        });
    }
});
