import './bootstrap';

// Mobile menu toggle. Everything else works without JavaScript.
const toggle = document.getElementById('nav-toggle');
const menu = document.getElementById('nav-menu');

if (toggle && menu) {
    toggle.addEventListener('click', () => {
        const open = menu.classList.toggle('hidden') === false;
        toggle.setAttribute('aria-expanded', String(open));
    });
}
import './chat';
