/**
 * Campus Clubs Management System — Simple Vanilla JavaScript
 * Handles mobile menu, password toggle, delete confirmation,
 * notice dismissal, and live club filtering.
 */
(function () {
    'use strict';

    // Mobile navigation toggle
    const navToggle = document.getElementById('navToggle');
    const mainNav = document.getElementById('mainNav');
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function () {
            const open = mainNav.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Show / Hide password toggle
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.getAttribute('data-toggle-password'));
            if (!input) return;
            const showing = input.type === 'password';
            input.type = showing ? 'text' : 'password';
            btn.textContent = showing ? 'Hide' : 'Show';
        });
    });

    // Dismiss flash notices
    document.querySelectorAll('[data-notice-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const box = btn.closest('[data-notice]');
            if (box) box.remove();
        });
    });

    // Confirm before destructive form submissions
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const message = form.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Instant client-side search filter on Clubs page
    document.querySelectorAll('[data-live-search]').forEach(function (input) {
        const items = document.querySelectorAll(input.getAttribute('data-live-search'));
        if (!items.length) return;
        input.addEventListener('input', function () {
            const q = input.value.trim().toLowerCase();
            items.forEach(function (card) {
                const text = (card.getAttribute('data-search-text') || card.textContent || '').toLowerCase();
                card.style.display = (q === '' || text.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    });
})();
