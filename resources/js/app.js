import './bootstrap';
import '@tabler/core/dist/js/tabler.min.js';

const media = window.matchMedia('(prefers-color-scheme: dark)');
const preferences = ['light', 'dark', 'system'];

function resolveTheme(preference) {
    return preference === 'system' ? (media.matches ? 'dark' : 'light') : preference;
}

function applyTheme(preference = localStorage.getItem('campus-theme') || 'system') {
    const safePreference = preferences.includes(preference) ? preference : 'system';
    document.documentElement.dataset.bsTheme = resolveTheme(safePreference);
    document.documentElement.dataset.themePreference = safePreference;
    document.querySelectorAll('[data-campus-theme-toggle]').forEach((button) => {
        const icon = button.querySelector('i');
        if (icon) icon.className = `ti ${safePreference === 'light' ? 'ti-sun' : safePreference === 'dark' ? 'ti-moon' : 'ti-device-desktop'}`;
        button.title = `Tema: ${safePreference}`;
    });
}

applyTheme();
media.addEventListener('change', () => {
    if ((localStorage.getItem('campus-theme') || 'system') === 'system') applyTheme('system');
});

document.addEventListener('DOMContentLoaded', () => {
    applyTheme();
    document.querySelectorAll('[data-campus-theme-toggle]').forEach((button) => button.addEventListener('click', () => {
        const current = localStorage.getItem('campus-theme') || 'system';
        const next = preferences[(preferences.indexOf(current) + 1) % preferences.length];
        localStorage.setItem('campus-theme', next);
        applyTheme(next);
    }));

    const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add('visible');
    }), { threshold: 0.12 });
    document.querySelectorAll('.reveal').forEach((element) => observer.observe(element));
});