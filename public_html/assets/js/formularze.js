(() => {
    const config = window.tabik?.config || {};

    const zbudujAdres = (sciezka = '') => {
        const bazowyUrl = String(config.bazowyUrl || '').replace(/\/+$/, '');
        const czysta = String(sciezka || '').trim();

        if (!czysta || czysta === '/') {
            return bazowyUrl || './';
        }

        if (/^https?:\/\//i.test(czysta) || czysta.startsWith('/')) {
            return czysta;
        }

        const bezPoczatku = czysta.replace(/^\/+/, '');
        if (!bazowyUrl) {
            return bezPoczatku;
        }

        return `${bazowyUrl}/${bezPoczatku}`.replace(/\/{2,}/g, '/');
    };

    const podstawParametryTrasy = (sciezka, params = {}) => {
        const uzyte = new Set();
        let wynik = String(sciezka || '').replace(/:([a-zA-Z_][a-zA-Z0-9_]*)/g, (caly, klucz) => {
            if (!Object.prototype.hasOwnProperty.call(params, klucz)) {
                throw new Error(`Brak parametru trasy: ${klucz}`);
            }

            uzyte.add(klucz);
            return encodeURIComponent(String(params[klucz]));
        });

        const query = new URLSearchParams();
        Object.entries(params || {}).forEach(([klucz, wartosc]) => {
            if (uzyte.has(klucz) || wartosc === null || wartosc === undefined || wartosc === '') return;
            query.set(klucz, String(wartosc));
        });

        const queryString = query.toString();
        if (queryString) wynik += `${wynik.includes('?') ? '&' : '?'}${queryString}`;
        return wynik;
    };

    const url = (nazwa, params = {}) => {
        if (window.tabik?.url) {
            return window.tabik.url(nazwa, params);
        }

        const mapa = config.routes || {};
        const sciezka = mapa[nazwa] || nazwa;
        return zbudujAdres(podstawParametryTrasy(sciezka, params));
    };

    const znajdzAdres = (formularz) => {
        const route = formularz.dataset.route || '';
        const action = formularz.getAttribute('action') || '';
        const mapa = config.routes || {};

        if (route && Object.prototype.hasOwnProperty.call(mapa, route)) {
            return url(route);
        }

        if (action) {
            return action;
        }

        return route ? url(route) : '';
    };

    const pokazKomunikat = (selektor, dane, fallback = 'Wystapil blad.') => {
        if (!selektor) return;
        const box = document.querySelector(selektor);
        if (!box) return;

        box.className = 'powiadomienie';
        box.textContent = dane?.komunikat || fallback;
        box.classList.add(dane?.sukces ? 'sukces' : 'blad');
        box.style.display = 'block';
    };

    const ukryjKomunikat = (selektor) => {
        if (!selektor) return;
        const box = document.querySelector(selektor);
        if (!box) return;
        box.className = 'powiadomienie';
        box.style.display = 'none';
        box.textContent = '';
    };

    const przelaczHaslo = (przycisk) => {
        const pole = document.querySelector(przycisk.getAttribute('data-przelacz-haslo'));
        if (!pole) return;

        const ikona = przycisk.querySelector('i');
        const czyHaslo = pole.getAttribute('type') === 'password';
        pole.setAttribute('type', czyHaslo ? 'text' : 'password');
        if (ikona) {
            ikona.classList.toggle('fa-eye', !czyHaslo);
            ikona.classList.toggle('fa-eye-slash', czyHaslo);
        }
    };

    const obsluzFormularzAjax = async (formularz) => {
        const endpoint = znajdzAdres(formularz);
        const selektorPowiadomienia = formularz.dataset.powiadomienie || '';
        if (!endpoint) {
            pokazKomunikat(selektorPowiadomienia, { sukces: false, komunikat: 'Brak skonfigurowanej trasy formularza.' });
            return;
        }

        const tekstLadowania = formularz.dataset.tekstLadowania || 'Wysylanie...';
        const redirectDomyslny = formularz.dataset.redirectDefault || '';
        const opoznienieRedirectu = Number(formularz.dataset.redirectDelay || 0);
        const przycisk = formularz.querySelector('button[type="submit"]');
        const pierwotnaEtykieta = przycisk?.textContent || '';

        ukryjKomunikat(selektorPowiadomienia);
        if (przycisk) {
            przycisk.disabled = true;
            przycisk.textContent = tekstLadowania;
        }

        try {
            const odpowiedz = await fetch(endpoint, {
                method: formularz.method?.toUpperCase() || 'POST',
                body: new FormData(formularz),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const dane = await odpowiedz.json().catch(() => ({ sukces: false, komunikat: 'Niepoprawna odpowiedz serwera.' }));

            if (!odpowiedz.ok || !dane.sukces) {
                pokazKomunikat(selektorPowiadomienia, dane, 'Nie udalo sie wyslac formularza.');
                return;
            }

            pokazKomunikat(selektorPowiadomienia, dane, 'Zapisano.');
            const redirect = dane.przekierowanie || redirectDomyslny;
            if (redirect) {
                window.setTimeout(() => { window.location.href = redirect; }, Number.isFinite(opoznienieRedirectu) ? opoznienieRedirectu : 0);
            }
        } finally {
            if (przycisk) {
                przycisk.disabled = false;
                przycisk.textContent = pierwotnaEtykieta;
            }
        }
    };

    document.addEventListener('click', (zdarzenie) => {
        const przycisk = zdarzenie.target.closest('[data-przelacz-haslo]');
        if (!przycisk) return;
        przelaczHaslo(przycisk);
    });

    document.addEventListener('submit', (zdarzenie) => {
        const formularz = zdarzenie.target.closest('[data-ajax-form]');
        if (!formularz) return;

        zdarzenie.preventDefault();
        obsluzFormularzAjax(formularz).catch(() => {
            pokazKomunikat(formularz.dataset.powiadomienie || '', { sukces: false, komunikat: 'Nie udalo sie polaczyc z serwerem.' });
        });
    });
})();
