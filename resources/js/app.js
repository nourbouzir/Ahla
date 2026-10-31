import './chat';

// Mobile menu: opens with a short pop animation. Everything else works without JavaScript.
const toggle = document.getElementById('nav-toggle');
const menu = document.getElementById('nav-menu');

if (toggle && menu) {
    toggle.addEventListener('click', () => {
        const opening = menu.classList.contains('hidden');
        menu.classList.toggle('hidden', !opening);
        menu.classList.toggle('flex', opening);
        menu.classList.toggle('menu-open', opening);
        toggle.setAttribute('aria-expanded', String(opening));
    });

    // Escape closes the menu and gives focus back to the button.
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            menu.classList.add('hidden');
            menu.classList.remove('flex', 'menu-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        }
    });
}

// Guide space: live countdown on requests that wait for an answer.
const countdowns = document.querySelectorAll('time[data-countdown]');

if (countdowns.length) {
    const tick = () => {
        countdowns.forEach((el) => {
            const left = new Date(el.dataset.countdown).getTime() - Date.now();

            if (left <= 0) {
                el.textContent = el.dataset.expired;

                return;
            }

            const minutes = Math.floor(left / 60000);
            el.textContent = minutes >= 60 ? `${Math.floor(minutes / 60)} h ${String(minutes % 60).padStart(2, '0')} min` : `${minutes} min`;
        });
    };

    tick();
    setInterval(tick, 30000);
}

// Guide space: keep the menu badges (requests to answer, unread messages) fresh without a reload.
const sideNav = document.querySelector('nav[data-summary]');

if (sideNav) {
    setInterval(async () => {
        if (document.hidden) return;

        try {
            const response = await fetch(sideNav.dataset.summary, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const counts = await response.json();

            Object.entries(counts).forEach(([route, value]) => {
                const badge = document.querySelector(`[data-live-count="${route}"] [data-n]`);
                if (badge) badge.textContent = value;
            });
        } catch (e) {
            // Offline for a moment: the next tick tries again.
        }
    }, 30000);
}

// Demo accounts panel on the login page (development only): fill the form with one click.
document.querySelectorAll('[data-demo-email]').forEach((button) => {
    button.addEventListener('click', () => {
        document.getElementById('email').value = button.dataset.demoEmail;
        document.getElementById('password').value = button.dataset.demoPassword;
        document.querySelector('form[action$="/login"] button[type="submit"]').focus();
    });
});
