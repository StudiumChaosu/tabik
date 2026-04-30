(() => {
    const html = document.documentElement;
    const tabik = window.tabik = window.tabik || {};
    const domyslnaPaleta = [
        'rgba(244, 67, 54, 1)',
        'rgba(233, 30, 99, 0.95)',
        'rgba(156, 39, 176, 0.9)',
        'rgba(103, 58, 183, 0.85)',
        'rgba(63, 81, 181, 0.8)',
        'rgba(33, 150, 243, 0.75)',
        'rgba(3, 169, 244, 0.7)',
        'rgba(0, 188, 212, 0.7)'
    ];

    const config = tabik.config = {
        bazowyUrl: html.dataset.bazowyUrl || '',
        tokenCsrf: html.dataset.tokenCsrf || '',
        routes: {},
        swatches: domyslnaPaleta,
        koloryUzytkownika: {},
        ...(tabik.config || {}),
    };

    if (!Array.isArray(config.swatches) || config.swatches.length === 0) {
        config.swatches = domyslnaPaleta;
    }

    if (!config.koloryUzytkownika || typeof config.koloryUzytkownika !== 'object') {
        config.koloryUzytkownika = {};
    }

    const tokenCsrf = config.tokenCsrf || html.dataset.tokenCsrf || '';
    const bazowyUrl = String(config.bazowyUrl || html.dataset.bazowyUrl || '').replace(/\/+$/, '');

    const zbudujAdres = (sciezka = '') => {
        const czysta = String(sciezka || '').trim();

        if (!czysta || czysta === '/') {
            return bazowyUrl || './';
        }

        if (/^https?:\/\//i.test(czysta)) {
            return czysta;
        }

        if (czysta.startsWith('/')) {
            return czysta;
        }

        const bezPoczatku = czysta.replace(/^\/+/, '');
        if (!bazowyUrl) {
            return bezPoczatku;
        }

        return `${bazowyUrl}/${bezPoczatku}`.replace(/\/+/g, '/');
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
        const mapa = config.routes || {};
        const sciezka = mapa[nazwa] || nazwa;
        return zbudujAdres(podstawParametryTrasy(sciezka, params));
    };


    const normalizujHex = (kolor, opcje = {}) => {
        const {
            domyslny = '#f5f7fb',
            dopuszczajAlfe = false,
            dopiszAlfe = '',
        } = opcje;
        const wartosc = String(kolor || '').trim().toLowerCase();

        if (/^#[0-9a-f]{6}$/.test(wartosc)) {
            return dopiszAlfe ? `${wartosc}${dopiszAlfe}` : wartosc;
        }

        if (/^#[0-9a-f]{8}$/.test(wartosc)) {
            return dopuszczajAlfe ? wartosc : wartosc.slice(0, 7);
        }

        return domyslny;
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
        const { params = {}, ...opcjeFetch } = opcje || {};
        const adres = url(sciezka, params);
        const metoda = (opcjeFetch.method || 'GET').toUpperCase();
        const ustawieniaBazowe = {
            method: metoda,
            headers: {
                'X-CSRF-Token': tokenCsrf,
                'X-Requested-With': 'XMLHttpRequest',
                ...(opcjeFetch.headers || {}),
            },
            credentials: 'same-origin',
            ...opcjeFetch,
        };

        if (!(ustawieniaBazowe.body instanceof FormData) && metoda !== 'GET') {
            ustawieniaBazowe.headers = {
                'Content-Type': 'application/json',
                ...ustawieniaBazowe.headers,
            };
        }

        const odpowiedz = await fetch(adres, ustawieniaBazowe);
        const tekst = await odpowiedz.text();
        let dane = null;

        try {
            dane = tekst ? JSON.parse(tekst) : {};
        } catch (e) {
            dane = { sukces: false, komunikat: 'Niepoprawna odpowiedz serwera.', surowa_odpowiedz: tekst };
        }

        if (!odpowiedz.ok) {
            throw Object.assign(new Error(dane.komunikat || `Blad HTTP ${odpowiedz.status}`), {
                dane,
                status: odpowiedz.status,
                url: adres,
            });
        }

        return dane;
    };

    const zapiszKolorUzytkownika = async (colorHex, obszar) => {
        const dozwolone = ['idkolor_zak', 'idkolor_gru', 'idkolor_prom'];
        if (!dozwolone.includes(obszar)) {
            throw new Error('Nieprawidlowy obszar koloru.');
        }

        const kolor = normalizujHex(colorHex, { domyslny: config.koloryUzytkownika[obszar] || '#f5f7fb' });
        const dane = await pobierzJson('api.ustawienia_kolorow', {
            method: 'POST',
            body: JSON.stringify({ obszar, kolor }),
        });

        if (!dane.sukces) {
            throw new Error(dane.komunikat || 'Nie udalo sie zapisac koloru.');
        }

        config.koloryUzytkownika[obszar] = dane.kolor || kolor;
        return dane;
    };

    const utworzPickrKoloruUzytkownika = (przycisk, opcje = {}) => {
        if (!window.Pickr || !przycisk || przycisk.dataset.pickrGotowy === '1') return null;

        const obszar = opcje.obszar || przycisk.dataset.kolorUzytkownikaObszar || '';
        const cssVar = opcje.cssVar || przycisk.dataset.kolorUzytkownikaCss || '';
        const domyslny = normalizujHex(opcje.domyslny || przycisk.dataset.kolorUzytkownikaDomyslny || '#f5f7fb');
        const kolorZKonfiguracji = config.koloryUzytkownika[obszar] || '';
        const kolorStartowy = normalizujHex(
            przycisk.style.getPropertyValue('--kolor-tla-modulu') || kolorZKonfiguracji || domyslny,
            { domyslny }
        );

        if (!obszar || !cssVar) return null;

        przycisk.style.setProperty('--kolor-tla-modulu', kolorStartowy);
        document.body?.style.setProperty(cssVar, kolorStartowy);
        przycisk.dataset.pickrGotowy = '1';

        const zapiszKolor = async (kolor) => {
            const hex = normalizujHex(kolor, { domyslny });
            przycisk.style.setProperty('--kolor-tla-modulu', hex);
            document.body?.style.setProperty(cssVar, hex);

            try {
                const dane = await zapiszKolorUzytkownika(hex, obszar);
                pokazPowiadomienie('sukces', dane.komunikat || opcje.komunikatSukces || 'Kolor zostal zapisany.');
            } catch (blad) {
                pokazPowiadomienie('blad', blad.message || 'Nie udalo sie zapisac koloru.');
            }
        };

        const picker = window.Pickr.create({
            el: przycisk,
            theme: 'monolith',
            appClass: opcje.appClass || 'tabik-pickr-tlo',
            useAsButton: true,
            default: kolorStartowy,
            defaultRepresentation: 'HEX',
            lockOpacity: true,
            comparison: true,
            closeOnScroll: true,
            autoReposition: true,
            swatches: config.swatches,
            components: {
                palette: true,
                preview: true,
                opacity: false,
                hue: true,
                interaction: {
                    hex: false,
                    rgba: false,
                    hsla: false,
                    hsva: false,
                    cmyk: false,
                    input: true,
                    clear: true,
                    save: true
                }
            },
            i18n: {
                'btn:toggle': 'Wybierz kolor',
                'btn:save': 'Zapisz',
                'btn:clear': 'Wyczysc'
            }
        });

        picker
            .on('change', (kolor) => {
                const hex = normalizujHex(kolor?.toHEXA ? kolor.toHEXA().toString() : kolorStartowy, { domyslny });
                przycisk.style.setProperty('--kolor-tla-modulu', hex);
                document.body?.style.setProperty(cssVar, hex);
            })
            .on('save', (kolor) => {
                const hex = normalizujHex(kolor?.toHEXA ? kolor.toHEXA().toString() : kolorStartowy, { domyslny });
                zapiszKolor(hex);
                picker.hide();
            })
            .on('clear', () => {
                zapiszKolor(domyslny);
            });

        return picker;
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

    const initPanelPrawy = () => {
        const panel = document.querySelector('[data-panel-kontekstowy]');
        const przyciski = document.querySelectorAll('[data-przelacz-prawy-panel]');
        const przyciskOtwierania = document.querySelector('.przycisk-zwin-prawy');
        const powloka = document.querySelector('.powloka-aplikacji');
        if (!panel || przyciski.length === 0) return;

        const odswiezStanPowloki = () => {
            if (!powloka) return;
            const panelDesktopUkryty = panel.classList.contains('jest-ukryty');
            const panelWTrybieKolumny = window.innerWidth > 1300;
            powloka.classList.toggle('panel-prawy-ukryty', panelDesktopUkryty || !panelWTrybieKolumny);
        };

        const odswiezPrzycisk = () => {
            const panelOverlayWidoczny = panel.classList.contains('jest-widoczny');
            const panelDesktopUkryty = panel.classList.contains('jest-ukryty');
            const pokazPrzycisk = window.innerWidth <= 1300 ? !panelOverlayWidoczny : panelDesktopUkryty;
            if (przyciskOtwierania) {
                przyciskOtwierania.classList.toggle('jest-widoczny', pokazPrzycisk);
                przyciskOtwierania.classList.toggle('jest-ukryty', !pokazPrzycisk);
            }
            odswiezStanPowloki();
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
        if (!pole || pole.dataset.datepickerGotowy === '1') return;

        const zrodla = [
            'https://cdn.jsdelivr.net/npm/air-datepicker@3.6.0/air-datepicker.js',
            'https://unpkg.com/air-datepicker@3.6.0/air-datepicker.js',
        ];
        const arkusze = [
            'https://cdn.jsdelivr.net/npm/air-datepicker@3.6.0/air-datepicker.css',
            'https://unpkg.com/air-datepicker@3.6.0/air-datepicker.css',
        ];

        const localePl = {
            days: ['Niedziela', 'Poniedzialek', 'Wtorek', 'Sroda', 'Czwartek', 'Piatek', 'Sobota'],
            daysShort: ['Nd', 'Pn', 'Wt', 'Sr', 'Cz', 'Pt', 'So'],
            daysMin: ['Nd', 'Pn', 'Wt', 'Sr', 'Cz', 'Pt', 'So'],
            months: ['Styczen', 'Luty', 'Marzec', 'Kwiecien', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpien', 'Wrzesien', 'Pazdziernik', 'Listopad', 'Grudzien'],
            monthsShort: ['Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Paz', 'Lis', 'Gru'],
            today: 'Dzisiaj',
            clear: 'Wyczysc',
            dateFormat: 'dd.MM.yyyy',
            timeFormat: 'HH:mm',
            firstDay: 1,
        };

        const pokazBlad = () => {
            pole.innerHTML = '<p class="komunikat-kalendarza">Nie udalo sie zaladowac Air Datepicker. Sprawdz w konsoli, czy przegladarka blokuje CDN.</p>';
        };

        const zaladujCss = () => {
            if (document.querySelector('link[data-air-datepicker-css]')) return;
            arkusze.forEach((href) => {
                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = href;
                css.dataset.airDatepickerCss = '1';
                document.head.appendChild(css);
            });
        };

        const uruchom = () => {
            if (typeof window.AirDatepicker !== 'function') {
                pokazBlad();
                return;
            }

            const dzisiaj = new Date();
            pole.innerHTML = '';
            pole.dataset.datepickerGotowy = '1';

            new window.AirDatepicker('#kalendarz-panelu', {
                inline: true,
                locale: localePl,
                startDate: dzisiaj,
                selectedDates: [dzisiaj],
                showOtherMonths: true,
                selectOtherMonths: true,
            });
        };

        const zaladujSkrypt = (indeks = 0) => {
            if (typeof window.AirDatepicker === 'function') {
                uruchom();
                return;
            }
            if (!zrodla[indeks]) {
                pokazBlad();
                return;
            }

            const skrypt = document.createElement('script');
            skrypt.src = zrodla[indeks];
            skrypt.defer = true;
            skrypt.dataset.airDatepickerJs = String(indeks + 1);
            skrypt.addEventListener('load', uruchom, { once: true });
            skrypt.addEventListener('error', () => zaladujSkrypt(indeks + 1), { once: true });
            document.head.appendChild(skrypt);
        };

        zaladujCss();
        zaladujSkrypt();
    };

    const initKoloryUzytkownika = () => {
        document.querySelectorAll('[data-kolor-uzytkownika-pickr]').forEach((przycisk) => {
            utworzPickrKoloruUzytkownika(przycisk);
        });
    };

    Object.assign(tabik, {
        url,
        normalizujHex,
        zapiszKolorUzytkownika,
        utworzPickrKoloruUzytkownika,
    });

    window.aplikacja = {
        config,
        tokenCsrf,
        bazowyUrl,
        adres: zbudujAdres,
        url,
        pokazPowiadomienie,
        pobierzJson,
        normalizujHex,
        zapiszKolorUzytkownika,
        utworzPickrKoloruUzytkownika,
    };

    document.addEventListener('DOMContentLoaded', () => {
        initZegar();
        initPanelPrawy();
        initModale();
        initDatepicker();
        initKoloryUzytkownika();
        document.querySelectorAll('#stos-powiadomien .powiadomienie').forEach((element) => {
            window.setTimeout(() => element.remove(), 4200);
        });
    });
})();
