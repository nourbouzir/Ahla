// k6 load test for Ahla (issue #38). Run against a server seeded with
//   php artisan db:seed --class=LoadTestSeeder
// and with AHLA_SEARCH_RATE_LIMIT raised, for example:
//   k6 run -e BASE_URL=https://staging.example.com tests/load/ahla.js
// Thresholds are the SRS FV numbers: pages 3 s (NFR-01), search 2 s (NFR-02), recommendations 5 s (NFR-03), all p95.
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const VUS = Number(__ENV.VUS || 20);

export const options = {
    scenarios: {
        pages: { executor: 'constant-vus', vus: VUS, duration: '1m', exec: 'pages' },
        search: { executor: 'constant-vus', vus: VUS, duration: '1m', exec: 'search' },
        recommendations: { executor: 'constant-vus', vus: VUS, duration: '1m', exec: 'recommendations' },
    },
    thresholds: {
        'http_req_duration{scenario:pages}': ['p(95)<3000'],
        'http_req_duration{scenario:search}': ['p(95)<2000'],
        'http_req_duration{scenario:recommendations}': ['p(95)<5000'],
        http_req_failed: ['rate<0.01'],
    },
};

const cities = ['Tunis', 'Sousse', 'Djerba', 'Sfax', 'Tozeur', 'Hammamet'];
const pick = (list) => list[Math.floor(Math.random() * list.length)];

export function pages() {
    for (const path of ['/', '/search', `/hosts/${1 + Math.floor(Math.random() * 300)}`, '/login']) {
        check(http.get(BASE + path), { ok: (r) => r.status === 200 });
    }
    sleep(1);
}

export function search() {
    const r = http.get(`${BASE}/api/search?city=${pick(cities)}&max_price=80&per_page=20`);
    check(r, { ok: (res) => res.status === 200 });
    sleep(1);
}

export function recommendations() {
    const r = http.get(`${BASE}/api/search?language=${pick(['en', 'fr', 'ar'])}&interests[]=${pick(['food', 'history', 'craft'])}&per_page=20`);
    check(r, { ok: (res) => res.status === 200, reasons: (res) => res.json('data.0.why_this_host.0') !== undefined });
    sleep(1);
}
