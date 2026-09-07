(function () {
    var theme = localStorage.getItem('selectedTheme') || 'light';
    if (theme !== 'dark' && theme !== 'light') theme = 'light';
    document.documentElement.setAttribute('data-bs-theme', theme);

    function updateThemeIcon() {
        var icon = document.getElementById('bd-theme-icon');
        if (!icon) return;
        var current = document.documentElement.getAttribute('data-bs-theme') || 'light';
        icon.className = current === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateThemeIcon();
        var button = document.getElementById('bd-theme');
        if (!button) return;
        button.addEventListener('click', function (event) {
            event.preventDefault();
            var current = document.documentElement.getAttribute('data-bs-theme') || 'light';
            var next = current === 'dark' ? 'light' : 'dark';
            localStorage.setItem('selectedTheme', next);
            document.documentElement.setAttribute('data-bs-theme', next);
            updateThemeIcon();
        });
    });
})();
