(() => {
    const html = document.documentElement;
    const tokenCsrf = html.dataset.tokenCsrf || '';
    const bazowyUrl = (html.dataset.bazowyUrl || '').replace(/\/$/, '');

    const zbudujAdres = (sciezka = '') => {
        const czysta = String(sciezka || '').trim();

        if (!czysta || czysta === '/') {
            return bazowyUrl || '';
        }

        if (/^https?:\/\//i.test(czysta)) {
            return czysta;
        }

        const bezPoczatku = czysta.replace(/^\/+/, '');
        return `${bazowyUrl}/${bezPoczatku}`.replace(/\/+/g, '/').replace(/^\/?(https?:)\//, '$1//');
    };

    const kandydaciAdresu = (sciezka = '') => {
        const wynik = [];
        const glowny = zbudujAdres(sciezka);
        if (glowny) wynik.push(glowny);

        const czysta = String(sciezka || '').trim().replace(/^\/+/, '');
        if (czysta) {
            wynik.push(`./${czysta}`);
            wynik.push(czysta);
            wynik.push(`/${czysta}`);
        }

        return [...new Set(wynik.filter(Boolean))];
    };

    const pokazPowiadomienie = (typ, tekst) => {
        if (!tekst) return;
        const stos = document.getElementById('stos-powiadomien');
        if (!stos) return;

        const element = document.createElement('div');
        element.className = `powiadomienie ${typ || ''}`;
        element.textContent = tekst;
        stos.appendChild(element);

        window.setTimeout(() => element.remove(), 4200);
    };

    const pobierzJson = async (sciezka, opcje = {}) => {
        const ustawieniaBazowe = {
            method: opcje.method || 'GET',
            headers: {
                'X-CSRF-Token': tokenCsrf,
                'X-Requested-With': 'XMLHttpRequest',
                ...(opcje.headers || {}),
            },
            credentials: 'same-origin',
            ...opcje,
        };

        if (!(ustawieniaBazowe.body instanceof FormData) && ustawieniaBazowe.method !== 'GET') {
            ustawieniaBazowe.headers = {
                'Content-Type': 'application/json',
                ...ustawieniaBazowe.headers,
            };
        }

        let ostatniBlad = null;

        for (const url of kandydaciAdresu(sciezka)) {
            try {
                const odpowiedz = await fetch(url, ustawieniaBazowe);
                const tekst = await odpowiedz.text();
                let dane = null;

                try {
                    dane = tekst ? JSON.parse(tekst) : {};
                } catch (e) {
                    dane = { sukces: false, komunikat: 'Niepoprawna odpowiedz serwera.', surowa_odpowiedz: tekst };
                }

                if (!odpowiedz.ok) {
                    ostatniBlad = Object.assign(new Error(dane.komunikat || `Blad HTTP ${odpowiedz.status}`), {
                        dane,
                        status: odpowiedz.status,
                        url,
                    });
                    continue;
                }

                return dane;
            } catch (blad) {
                ostatniBlad = blad;
            }
        }

        throw ostatniBlad || new Error('Nie udalo sie polaczyc z serwerem.');
    };

    const initZegar = () => {
        const pole = document.querySelector('[data-zegar]');
        if (!pole) return;
        const odswiez = () => {
            pole.textContent = new Date().toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });
        };
        odswiez();
        window.setInterval(odswiez, 1000);
    };

    const initPanelBoczny = () => {
        const panel = document.querySelector('[data-panel-boczny]');
        const przycisk = document.querySelector('[data-przelacz-panel-boczny]');
        if (!panel || !przycisk) return;
        const klucz = 'pulpit_zakladek_panel_boczny_zwiniety';
        const ustawStan = (czyZwiniety) => {
            panel.classList.toggle('jest-zwiniety', czyZwiniety);
            localStorage.setItem(klucz, czyZwiniety ? '1' : '0');
        };
        ustawStan(localStorage.getItem(klucz) === '1');
        przycisk.addEventListener('click', () => ustawStan(!panel.classList.contains('jest-zwiniety')));
    };

    const initPanelPrawy = () => {
        const panel = document.querySelector('[data-panel-kontekstowy]');
        const przyciski = document.querySelectorAll('[data-przelacz-prawy-panel]');
        const przyciskOtwierania = document.querySelector('.przycisk-zwin-prawy');
        if (!panel || przyciski.length === 0) return;

        const odswiezPrzycisk = () => {
            const panelOverlayWidoczny = panel.classList.contains('jest-widoczny');
            const panelDesktopUkryty = panel.classList.contains('jest-ukryty');
            const pokazPrzycisk = window.innerWidth <= 1300 ? !panelOverlayWidoczny : panelDesktopUkryty;
            if (przyciskOtwierania) {
                przyciskOtwierania.classList.toggle('jest-widoczny', pokazPrzycisk);
                przyciskOtwierania.classList.toggle('jest-ukryty', !pokazPrzycisk);
            }
        };

        przyciski.forEach((przycisk) => {
            przycisk.addEventListener('click', () => {
                if (window.innerWidth <= 1300) panel.classList.toggle('jest-widoczny');
                else panel.classList.toggle('jest-ukryty');
                odswiezPrzycisk();
            });
        });

        window.addEventListener('resize', odswiezPrzycisk);
        odswiezPrzycisk();
    };

    const initModale = () => {
        document.addEventListener('click', (zdarzenie) => {
            const otworz = zdarzenie.target.closest('[data-otworz-modal]');
            if (otworz) {
                const selektor = otworz.getAttribute('data-otworz-modal');
                const modal = document.querySelector(selektor);
                if (modal) modal.classList.remove('ukryta');
            }
            const zamknij = zdarzenie.target.closest('[data-zamknij-modal]');
            if (zamknij) {
                const selektor = zamknij.getAttribute('data-zamknij-modal');
                const modal = document.querySelector(selektor);
                if (modal) modal.classList.add('ukryta');
            }
            if (zdarzenie.target.classList.contains('warstwa-modalna')) zdarzenie.target.classList.add('ukryta');
        });

        document.addEventListener('keydown', (zdarzenie) => {
            if (zdarzenie.key === 'Escape') {
                document.querySelectorAll('.warstwa-modalna').forEach((modal) => modal.classList.add('ukryta'));
            }
        });
    };

    const initDatepicker = () => {
        const pole = document.getElementById('kalendarz-panelu');
        if (!pole || typeof window.AirDatepicker === 'undefined') return;
        new AirDatepicker(pole, { inline: true, locale: window.localePl || undefined });
    };

    document.addEventListener('DOMContentLoaded', () => {
        initZegar();
        initPanelBoczny();
        initPanelPrawy();
        initModale();
        initDatepicker();
        document.querySelectorAll('#stos-powiadomien .powiadomienie').forEach((element) => {
            window.setTimeout(() => element.remove(), 4200);
        });
    });

    window.aplikacja = {
        tokenCsrf,
        bazowyUrl,
        adres: zbudujAdres,
        pokazPowiadomienie,
        pobierzJson,
    };
})();
