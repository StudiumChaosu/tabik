(() => {
    const znajdzAdres = (formularz) => {
        const endpoint = formularz.dataset.endpoint || '';
        const route = formularz.dataset.route || '';

        if (route && window.tabik?.url) {
            return window.tabik.url(route);
        }

        return endpoint || formularz.getAttribute('action') || '';
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
        if (!endpoint) return;

        const selektorPowiadomienia = formularz.dataset.powiadomienie || '';
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
