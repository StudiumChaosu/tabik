(() => {
    const api = window.aplikacja;
    if (!api) return;

    const root = document.querySelector('[data-zakladki-app]');
    if (!root) return;

    const daneStartowe = document.getElementById('zakladki-dane-startowe');
    let poczatkowe = {};
    try {
        poczatkowe = daneStartowe ? JSON.parse(daneStartowe.textContent || '{}') : {};
    } catch (e) {
        poczatkowe = {};
    }

    const stan = {
        dane: poczatkowe,
        aktywneMenu: null,
        kolorPicker: null,
        timerKoloru: null,
        edycjaNazwyGrupyInline: null,
        przeciaganaGrupa: null,
        przeniesionoGrupeDoKategorii: false,
    };

    const els = {
        pasekKategorii: document.getElementById('pasek-kategorii'),
        obszarKolumn: document.getElementById('obszar-kolumn'),
        fileInput: document.getElementById('pole-importu-json'),
        searchTop: document.getElementById('pole-szukania-zakladek'),
        searchPanel: document.getElementById('pole-szukania-zakladek-panel'),
        modalZakladka: document.getElementById('modal-zakladka'),
        modalGrupa: document.getElementById('modal-grupa'),
        modalKategoria: document.getElementById('modal-kategoria'),
        formZakladka: document.getElementById('formularz-zakladki'),
        formGrupa: document.getElementById('formularz-grupy'),
        formKategoria: document.getElementById('formularz-kategorii'),
        poleIdZakladki: document.getElementById('pole-id-zakladki'),
        poleTytulZakladki: document.getElementById('pole-tytul-zakladki'),
        poleAdresZakladki: document.getElementById('pole-adres-zakladki'),
        poleGrupaZakladki: document.getElementById('pole-grupa-zakladki'),
        poleKategoriaZakladki: document.getElementById('pole-kategoria-zakladki'),
        poleOpisZakladki: document.getElementById('pole-opis-zakladki'),
        poleUlubionaZakladki: document.getElementById('pole-ulubiona-zakladki'),
        poleIdGrupy: document.getElementById('pole-id-grupy'),
        poleNazwaGrupy: document.getElementById('pole-nazwa-grupy'),
        poleIdKategoriiGrupy: document.getElementById('pole-id-kategorii-grupy'),
        poleNazwaKategorii: document.getElementById('pole-nazwa-kategorii'),
        tytulModaluZakladki: document.getElementById('tytul-modalu-zakladki'),
        tytulModaluGrupy: document.getElementById('tytul-modalu-grupy'),
    };

    const esc = (v) => String(v ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const filtry = () => ({ ...(stan.dane.filtry || {}) });
    const pokazModal = (m) => m?.classList.remove('ukryta');
    const ukryjModal = (m) => m?.classList.add('ukryta');

    const DOMYSLNY_KOLOR_GRUPY = '#d8b50030';
    const DOMYSLNA_ALFA_GRUPY = '30';
    const normalizujKolor = (kolor, domyslny = DOMYSLNY_KOLOR_GRUPY) => {
        const wartosc = String(kolor || '').trim().toLowerCase();
        if (/^#[0-9a-f]{6}$/.test(wartosc)) return `${wartosc}${DOMYSLNA_ALFA_GRUPY}`;
        if (/^#[0-9a-f]{8}$/.test(wartosc)) return wartosc;
        return domyslny;
    };
    const kolorTekstuDlaTla = (kolor) => {
        const hex = normalizujKolor(kolor).slice(1);
        const r = parseInt(hex.slice(0, 2), 16);
        const g = parseInt(hex.slice(2, 4), 16);
        const b = parseInt(hex.slice(4, 6), 16);
        const a = hex.length >= 8 ? parseInt(hex.slice(6, 8), 16) / 255 : 1;
        const rr = Math.round((r * a) + (245 * (1 - a)));
        const gg = Math.round((g * a) + (245 * (1 - a)));
        const bb = Math.round((b * a) + (235 * (1 - a)));
        const jasnosc = ((rr * 299) + (gg * 587) + (bb * 114)) / 1000;
        return jasnosc >= 150 ? '#0f172a' : '#ffffff';
    };
    const kolorGrupy = (grupa) => normalizujKolor(grupa?.kolor || DOMYSLNY_KOLOR_GRUPY);
    const paletaPickrGrupy = [
        'rgba(244, 67, 54, 1)',
        'rgba(233, 30, 99, 0.95)',
        'rgba(156, 39, 176, 0.9)',
        'rgba(103, 58, 183, 0.85)',
        'rgba(63, 81, 181, 0.8)',
        'rgba(33, 150, 243, 0.75)',
        'rgba(3, 169, 244, 0.7)',
        'rgba(0, 188, 212, 0.7)'
    ];
    const liczba = (v) => Number(v || 0);

    const faviconFallbacki = (adresUrl, favIconUrl = '') => {
        const fav = String(favIconUrl || '').trim();
        const adres = String(adresUrl || '').trim();
        const wyniki = [];

        if (fav) wyniki.push(fav);

        try {
            const url = new URL(adres);
            const domena = url.hostname;
            const origin = url.origin;
            if (domena) {
                wyniki.push(`https://www.google.com/s2/favicons?domain=${encodeURIComponent(domena)}&sz=64`);
                wyniki.push(`https://icons.duckduckgo.com/ip3/${encodeURIComponent(domena)}.ico`);
                wyniki.push(`${origin}/favicon.ico`);
            }
        } catch (e) {
            // brak poprawnego URL - zostanie zastosowana ikonka zastpcza
        }

        return [...new Set(wyniki.filter(Boolean))];
    };

    const faviconHtml = (zakladka) => {
        const fallbacki = faviconFallbacki(zakladka.adres_url, zakladka.favIconUrl);
        const fallbackData = esc(JSON.stringify(fallbacki));
        if (fallbacki.length) {
            const pierwszy = esc(fallbacki[0]);
            return `<img class="ikona-linku" src="${pierwszy}" alt="" loading="lazy" referrerpolicy="no-referrer" data-fallbacks='${fallbackData}' onerror="window.obsluzBladFavikony?.(this)">`;
        }
        return '<span class="ikona-linku zastpcza" aria-hidden="true"><i class="fa-solid fa-globe"></i></span>';
    };

    const aktualizujPolaSzukania = (wartosc) => {
        [els.searchTop, els.searchPanel].forEach((pole) => {
            if (pole && pole.value !== wartosc) pole.value = wartosc;
        });
    };

    const znajdzZakladke = (id) => {
        for (const grupa of stan.dane.grupy || []) {
            const trafiona = (grupa.zakladki || []).find((z) => Number(z.id) === Number(id));
            if (trafiona) return trafiona;
        }
        return null;
    };

    const znajdzGrupe = (id) => (stan.dane.grupy || []).find((g) => Number(g.id) === Number(id)) || null;

    const zniszczPickerKoloruGrupy = () => {
        if (!stan.kolorPicker?.picker) return;
        try {
            stan.kolorPicker.picker.destroyAndRemove?.();
        } catch (e) {
            try { stan.kolorPicker.picker.destroy?.(); } catch (ignore) {}
        }
        document.querySelectorAll('.menu-grupy.ma-picker-otwarty').forEach((el) => el.classList.remove('ma-picker-otwarty'));
        stan.kolorPicker = null;
    };

    const zamknijMenu = () => {
        stan.aktywneMenu = null;
        zniszczPickerKoloruGrupy();
        document.querySelectorAll('.menu-grupy').forEach((el) => el.classList.add('ukryte'));
    };

    window.obsluzBladFavikony = (img) => {
        if (!img) return;
        let fallbacki = [];
        try {
            fallbacki = JSON.parse(img.dataset.fallbacks || '[]');
        } catch (e) {
            fallbacki = [];
        }
        const aktualny = img.getAttribute('src');
        const indeks = fallbacki.indexOf(aktualny);
        const nastepny = indeks >= 0 ? fallbacki[indeks + 1] : fallbacki[0];
        if (nastepny && nastepny !== aktualny) {
            img.src = nastepny;
            return;
        }
        const zastpcza = document.createElement('span');
        zastpcza.className = 'ikona-linku zastpcza';
        zastpcza.setAttribute('aria-hidden', 'true');
        zastpcza.innerHTML = '<i class="fa-solid fa-globe"></i>';
        img.replaceWith(zastpcza);
    };

    const renderujKategorie = () => {
        const aktywna = filtry().id_kategorii;
        const kategorie = stan.dane.kategorie || [];
        els.pasekKategorii.innerHTML = `
            <div class="lista-kategorii-top">
                <div class="lista-kategorii-sortowalna" data-sort-kategorie="1">
                    ${kategorie.map((kategoria) => `
                        <button type="button" class="tab-kategorii ${Number(aktywna) === Number(kategoria.id) ? 'jest-aktywna' : ''}" data-akcja="filtr-kategoria" data-id-kategorii="${Number(kategoria.id)}" data-drop-kategoria="1" title="Przeciagnij, aby zmienic kolejnosc kategorii">
                            ${esc(kategoria.nazwa)}
                        </button>
                    `).join('')}
                </div>
                <button type="button" class="tab-kategorii ${String(aktywna) === '0' ? 'jest-aktywna' : ''}" data-akcja="filtr-kategoria" data-id-kategorii="0" data-drop-kategoria="1">Bez kategorii</button>
                <button type="button" class="tab-kategorii tab-dodaj" data-akcja="dodaj-kategorie" title="Dodaj kategorie">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        `;
    };

    const renderujKolumny = () => {
        const ukryteNazwyGrup = new Set(['szybki start', 'do sprawdzenia']);
        const grupy = (stan.dane.grupy || []).filter((grupa) => !ukryteNazwyGrup.has(String(grupa.nazwa || '').trim().toLowerCase()));
        els.obszarKolumn.innerHTML = `${grupy.map((grupa) => {
            const zakladki = grupa.czy_zwinieta ? [] : (grupa.zakladki || []);
            const kolor = kolorGrupy(grupa);
            const maId = Number(grupa.id) > 0;
            return `
                <article class="kolumna-grupy-kompakt ${maId ? '' : 'kolumna-bez-grupy'}" data-id-grupy="${Number(grupa.id || 0)}" style="--kolor-grupy:${esc(kolor)};--kolor-tekstu-grupy:${esc(kolorTekstuDlaTla(kolor))};">
                    <header class="naglowek-grupy-kompakt" data-edytuj-grupe-prawym="1">
                        <div class="tytul-grupy-kompakt">
                            <h4 class="nazwa-grupy-kompakt" title="Kliknij prawym, aby zmienic nazwe">${esc(grupa.nazwa || 'Bez grupy')}</h4>
                        </div>
                        ${maId ? `
                            <div class="akcje-grupy-kompakt">
                                <button type="button" class="przycisk-menu-grupy" data-akcja="przelacz-menu-grupy" data-id-grupy="${Number(grupa.id)}" title="Opcje grupy">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <div class="menu-grupy ukryte" id="menu-grupy-${Number(grupa.id)}">
                                    <div class="menu-kolor-wiersz" aria-label="Kolor naglowka grupy">
                                        <span>Kolor</span>
                                        <button type="button" class="kolor-picker-grupy-widget" data-pickr-kolor-grupy data-id-grupy="${Number(grupa.id)}" title="Wybierz kolor naglowka"></button>
                                    </div>
                                    <button type="button" class="przycisk-menu-opcja" data-akcja="dodaj-zakladke-do-grupy" data-id-grupy="${Number(grupa.id)}">+ Zakladke</button>
                                    <button type="button" class="przycisk-menu-opcja" data-akcja="otworz-wszystkie" data-id-grupy="${Number(grupa.id)}">Otworz wszystkie</button>
                                    <button type="button" class="przycisk-menu-opcja usun" data-akcja="usun-grupe" data-id-grupy="${Number(grupa.id)}">Usun</button>
                                </div>
                            </div>
                        ` : ''}
                    </header>
                    <div class="lista-linkow-w-grupie" data-id-grupy="${Number(grupa.id || 0)}">
                        ${zakladki.length ? zakladki.map((zakladka) => `
                            <div class="element-linku" data-id-zakladki="${Number(zakladka.id)}">
                                <div class="link-glowny">
                                    ${faviconHtml(zakladka)}
                                    <a class="link-tytul" href="${esc(zakladka.adres_url)}" target="_blank" rel="noopener noreferrer" title="${esc(zakladka.adres_url)}">${esc(zakladka.tytul)}</a>
                                </div>
                                <div class="link-akcje">
                                    <button type="button" class="przycisk-linku-akcji ${Number(zakladka.czy_ulubiona) === 1 ? 'jest-ulubiona' : ''}" data-akcja="przelacz-ulubiona" data-id-zakladki="${Number(zakladka.id)}" title="Ulubione"><i class="fa-solid fa-star"></i></button>
                                    <button type="button" class="przycisk-linku-akcji" data-akcja="edytuj-zakladke" data-id-zakladki="${Number(zakladka.id)}" title="Edytuj"><i class="fa-solid fa-pen"></i></button>
                                    <button type="button" class="przycisk-linku-akcji" data-akcja="usun-zakladke" data-id-zakladki="${Number(zakladka.id)}" title="Usun"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                        `).join('') : '<div class="puste-grupy">Przeciagnij tutaj zakladke albo dodaj nowa.</div>'}
                    </div>
                </article>
            `;
        }).join('')}
        <button type="button" class="karta-dodaj-grupe" data-akcja="dodaj-grupe">
            <span class="plus"><i class="fa-solid fa-plus"></i></span>
            <span>Dodaj grupe</span>
        </button>`;
    };

    const renderuj = () => {
        renderujKategorie();
        renderujKolumny();
        uzupelnijSelecty();
        uruchomSortowanie();
        aktualizujPolaSzukania(stan.dane.filtry?.q || '');
    };

    const pobierzListe = async (nadpisania = {}) => {
        const aktywne = { ...filtry(), ...nadpisania };
        const params = new URLSearchParams();
        Object.entries(aktywne).forEach(([klucz, wartosc]) => {
            if (wartosc !== null && wartosc !== undefined && wartosc !== '') params.set(klucz, String(wartosc));
        });
        const odpowiedz = await api.pobierzJson(`api/zakladki/lista.php?${params.toString()}`);
        stan.dane = odpowiedz.dane || {};
        renderuj();
    };

    const uzupelnijSelecty = () => {
        const grupy = (stan.dane.grupy || []).filter((g) => Number(g.id) > 0);
        const kategorie = stan.dane.kategorie || [];
        els.poleGrupaZakladki.innerHTML = ['<option value="">Bez grupy</option>', ...grupy.map((g) => `<option value="${Number(g.id)}">${esc(g.nazwa)}</option>`)].join('');
        els.poleKategoriaZakladki.innerHTML = ['<option value="">Bez kategorii</option>', ...kategorie.map((k) => `<option value="${Number(k.id)}">${esc(k.nazwa)}</option>`)].join('');
    };

    const otworzModalZakladki = (tryb = 'dodaj', zakladka = null, idGrupy = '') => {
        els.formZakladka.reset();
        uzupelnijSelecty();
        els.poleIdZakladki.value = '';
        els.tytulModaluZakladki.textContent = tryb === 'edytuj' ? 'Edytuj zakladke' : 'Dodaj zakladke';
        if (tryb === 'edytuj' && zakladka) {
            els.poleIdZakladki.value = zakladka.id || '';
            els.poleTytulZakladki.value = zakladka.tytul || '';
            els.poleAdresZakladki.value = zakladka.adres_url || '';
            els.poleGrupaZakladki.value = zakladka.id_grupy ?? '';
            els.poleKategoriaZakladki.value = zakladka.id_kategorii ?? '';
            els.poleOpisZakladki.value = zakladka.opis || '';
            els.poleUlubionaZakladki.checked = Number(zakladka.czy_ulubiona) === 1;
        } else if (idGrupy !== '' && idGrupy !== null && Number(idGrupy) > 0) {
            els.poleGrupaZakladki.value = String(idGrupy);
        }
        pokazModal(els.modalZakladka);
    };

    const otworzModalGrupy = (grupa = null) => {
        els.formGrupa?.reset();
        if (!els.poleIdGrupy || !els.poleNazwaGrupy || !els.tytulModaluGrupy || !els.modalGrupa) return;
        const aktywnaKategoria = filtry().id_kategorii ?? '';
        const pierwszaKategoria = (stan.dane.kategorie || [])[0]?.id ?? '';
        const idKategorii = grupa?.id_kategorii ?? aktywnaKategoria ?? pierwszaKategoria ?? '';
        els.poleIdGrupy.value = grupa?.id || '';
        if (els.poleIdKategoriiGrupy) els.poleIdKategoriiGrupy.value = idKategorii === null ? '' : String(idKategorii);
        els.poleNazwaGrupy.value = grupa?.nazwa || '';
        els.tytulModaluGrupy.textContent = grupa ? 'Zmien nazwe grupy' : 'Dodaj grupe';
        pokazModal(els.modalGrupa);
        window.setTimeout(() => {
            els.poleNazwaGrupy?.focus();
            if (grupa) els.poleNazwaGrupy?.select();
        }, 0);
    };

    const zapiszZakladke = async (e) => {
        e.preventDefault();
        const formData = new FormData(els.formZakladka);
        const czyEdycja = Number(els.poleIdZakladki.value) > 0;
        const url = czyEdycja ? 'api/zakladki/edytuj.php' : 'api/zakladki/dodaj.php';
        const odpowiedz = await fetch(api.adres(url), {
            method: 'POST',
            headers: { 'X-CSRF-Token': api.tokenCsrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
            credentials: 'same-origin',
        });
        const tekst = await odpowiedz.text();
        let dane = {};
        try {
            dane = tekst ? JSON.parse(tekst) : {};
        } catch (e) {
            dane = { sukces: false, komunikat: 'Niepoprawna odpowiedz serwera.' };
        }
        if (!odpowiedz.ok || dane.sukces === false) throw new Error(dane.komunikat || 'Nie udalo sie zapisac zakladki.');
        api.pokazPowiadomienie('sukces', dane.komunikat || 'Zapisano.');
        ukryjModal(els.modalZakladka);
        await pobierzListe();
    };

    const zapiszGrupe = async (e) => {
        e.preventDefault();
        const id = Number(els.poleIdGrupy?.value || 0);
        const nazwa = els.poleNazwaGrupy?.value.trim() || '';
        const idKategorii = els.poleIdKategoriiGrupy?.value ?? '';
        if (!nazwa) return;
        if (idKategorii === '') {
            throw new Error('Najpierw dodaj lub wybierz kategorie dla grupy.');
        }
        const fd = new FormData();
        fd.set('nazwa', nazwa);
        if (id > 0) fd.set('id', String(id));
        fd.set('id_kategorii', String(idKategorii));
        const odpowiedz = await api.pobierzJson(id > 0 ? 'api/zakladki/grupy/edytuj.php' : 'api/zakladki/grupy/dodaj.php', { method: 'POST', body: fd });
        api.pokazPowiadomienie('sukces', odpowiedz.komunikat || 'Zapisano grupe.');
        ukryjModal(els.modalGrupa);
        await pobierzListe();
    };

    const zapiszNazweGrupyInline = async (idGrupy, nazwa) => {
        const czystaNazwa = String(nazwa || '').trim();
        if (Number(idGrupy) <= 0 || czystaNazwa.length < 2) {
            throw new Error('Nazwa grupy musi miec minimum 2 znaki.');
        }

        const grupa = znajdzGrupe(idGrupy);
        if (grupa && String(grupa.nazwa || '').trim() === czystaNazwa) return false;

        const fd = new FormData();
        fd.set('id', String(Number(idGrupy)));
        fd.set('nazwa', czystaNazwa);
        await api.pobierzJson('api/zakladki/grupy/edytuj.php', { method: 'POST', body: fd });
        if (grupa) grupa.nazwa = czystaNazwa;
        await pobierzListe();
        return true;
    };

    const zaznaczTekstElementu = (element) => {
        if (!element) return;
        const zakres = document.createRange();
        zakres.selectNodeContents(element);
        const wybor = window.getSelection();
        wybor?.removeAllRanges();
        wybor?.addRange(zakres);
    };

    const ustawTrybEdycjiNaglowka = (h4, czyEdytowany) => {
        if (!h4) return;
        h4.contentEditable = czyEdytowany ? 'true' : 'false';
        h4.spellcheck = false;
        h4.classList.toggle('jest-edytowany-inline', Boolean(czyEdytowany));
        if (czyEdytowany) {
            h4.setAttribute('role', 'textbox');
            h4.setAttribute('aria-label', 'Nazwa grupy');
        } else {
            h4.removeAttribute('role');
            h4.removeAttribute('aria-label');
        }
    };

    const rozpocznijEdycjeNazwyGrupy = (idGrupy) => {
        const id = Number(idGrupy || 0);
        if (id <= 0) return;

        const grupa = znajdzGrupe(id);
        const kolumna = els.obszarKolumn?.querySelector(`.kolumna-grupy-kompakt[data-id-grupy="${id}"]`);
        const naglowek = kolumna?.querySelector('.naglowek-grupy-kompakt');
        const h4 = kolumna?.querySelector('.tytul-grupy-kompakt h4');
        if (!grupa || !kolumna || !naglowek || !h4) return;

        if (h4.isContentEditable) {
            h4.focus();
            zaznaczTekstElementu(h4);
            return;
        }

        zamknijMenu();
        stan.edycjaNazwyGrupyInline = id;
        naglowek.classList.add('jest-edytowany-inline');

        const staraNazwa = String(grupa.nazwa || '').trim() || 'Bez grupy';
        h4.dataset.staraNazwa = staraNazwa;
        h4.textContent = staraNazwa;
        ustawTrybEdycjiNaglowka(h4, true);

        let zakonczone = false;

        function wyczyscObslugeEdycji() {
            h4.removeEventListener('keydown', obsluzKlawisze);
            h4.removeEventListener('paste', obsluzWklejanie);
            h4.removeEventListener('blur', obsluzBlur);
        }

        function przywroc(nazwa) {
            h4.textContent = nazwa || staraNazwa;
            h4.classList.remove('jest-zapisywany');
            ustawTrybEdycjiNaglowka(h4, false);
            delete h4.dataset.staraNazwa;
            naglowek.classList.remove('jest-edytowany-inline');
            stan.edycjaNazwyGrupyInline = null;
            wyczyscObslugeEdycji();
        }

        const zakoncz = async (czyAnulowac = false) => {
            if (zakonczone) return;
            zakonczone = true;
            const nowaNazwa = h4.textContent.trim().replace(/\s+/g, ' ');

            if (czyAnulowac) {
                przywroc(staraNazwa);
                return;
            }

            if (!nowaNazwa || nowaNazwa.length < 2) {
                zakonczone = false;
                api.pokazPowiadomienie('blad', 'Nazwa grupy musi miec minimum 2 znaki.');
                h4.textContent = staraNazwa;
                h4.focus();
                zaznaczTekstElementu(h4);
                return;
            }

            if (nowaNazwa === staraNazwa) {
                przywroc(staraNazwa);
                return;
            }

            h4.classList.add('jest-zapisywany');
            try {
                await zapiszNazweGrupyInline(id, nowaNazwa);
            } catch (blad) {
                h4.classList.remove('jest-zapisywany');
                zakonczone = false;
                api.pokazPowiadomienie('blad', blad.message || 'Nie udalo sie zapisac nazwy grupy.');
                h4.textContent = staraNazwa;
                h4.focus();
                zaznaczTekstElementu(h4);
                return;
            }
            przywroc(nowaNazwa);
        };

        function obsluzKlawisze(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                h4.blur();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                zakoncz(true);
            }
        }

        function obsluzWklejanie(e) {
            e.preventDefault();
            const tekst = (e.clipboardData || window.clipboardData)?.getData('text') || '';
            document.execCommand('insertText', false, tekst.replace(/\s+/g, ' ').trim());
        }

        function obsluzBlur() {
            zakoncz(false);
        }

        h4.addEventListener('keydown', obsluzKlawisze);
        h4.addEventListener('paste', obsluzWklejanie);
        h4.addEventListener('blur', obsluzBlur);

        window.setTimeout(() => {
            h4.focus();
            zaznaczTekstElementu(h4);
        }, 0);
    };

    const zapiszKategorie = async (e) => {
        e.preventDefault();
        const nazwa = els.poleNazwaKategorii.value.trim();
        if (!nazwa) return;
        const fd = new FormData();
        fd.set('nazwa', nazwa);
        const odpowiedz = await api.pobierzJson('api/zakladki/kategorie/dodaj.php', { method: 'POST', body: fd });
        api.pokazPowiadomienie('sukces', odpowiedz.komunikat || 'Dodano kategorie.');
        ukryjModal(els.modalKategoria);
        els.formKategoria.reset();
        await pobierzListe();
    };

    const usunZakladke = async (id) => {
        if (!window.confirm('Usunac te zakladke?')) return;
        const odpowiedz = await api.pobierzJson('api/zakladki/usun.php', { method: 'POST', body: JSON.stringify({ id }) });
        api.pokazPowiadomienie('sukces', odpowiedz.komunikat || 'Usunieto zakladke.');
        await pobierzListe();
    };

    const przelaczUlubiona = async (id) => {
        await api.pobierzJson('api/zakladki/ulubiona/przelacz.php', { method: 'POST', body: JSON.stringify({ id }) });
        await pobierzListe();
    };

    const zapamietajOstaniąKategorię = async (idKategorii) => {
        try {
            await api.pobierzJson('api/zakladki/zapisz-ostatnia-kategorie.php', {
                method: 'POST',
                body: JSON.stringify({ id_kategorii: idKategorii })
            });
        } catch (e) {
            // Błąd przy zapisie ostatniej kategorii nie powinien przerywać działania aplikacji
            console.warn('Nie udało się zapamiętać ostatniej kategorii:', e.message);
        }
    };

    const otworzWszystkie = (idGrupy) => {
        const grupa = znajdzGrupe(idGrupy);
        (grupa?.zakladki || []).forEach((zakladka) => window.open(zakladka.adres_url, '_blank', 'noopener'));
    };

    const zastosujKolorGrupyLokalnie = (idGrupy, kolor) => {
        const kolorHex = normalizujKolor(kolor);
        const grupa = znajdzGrupe(idGrupy);
        if (grupa) grupa.kolor = kolorHex;

        const kolumna = document.querySelector(`.kolumna-grupy-kompakt[data-id-grupy="${Number(idGrupy)}"]`);
        if (kolumna) {
            kolumna.style.setProperty('--kolor-grupy', kolorHex);
            kolumna.style.setProperty('--kolor-tekstu-grupy', kolorTekstuDlaTla(kolorHex));
        }
    };

    const zapiszKolorGrupy = async (idGrupy, kolor, czyCicho = false) => {
        const kolorHex = normalizujKolor(kolor);
        zastosujKolorGrupyLokalnie(idGrupy, kolorHex);
        await api.pobierzJson('api/zakladki/grupy/kolor.php', {
            method: 'POST',
            body: JSON.stringify({ id: Number(idGrupy), kolor: kolorHex }),
        });
    };

    const zapiszKolorGrupyZDebounce = (idGrupy, kolor) => {
        window.clearTimeout(stan.timerKoloru);
        stan.timerKoloru = window.setTimeout(() => {
            zapiszKolorGrupy(idGrupy, kolor, true).catch((blad) => {
                api.pokazPowiadomienie('blad', blad.message || 'Nie udalo sie zapisac koloru grupy.');
            });
        }, 220);
    };

    const przygotujPickerKoloruGrupy = (idGrupy) => {
        const menu = document.getElementById(`menu-grupy-${Number(idGrupy)}`);
        const przycisk = menu?.querySelector('[data-pickr-kolor-grupy]');
        if (!menu || !przycisk) return;

        zniszczPickerKoloruGrupy();

        if (!window.Pickr) {
            api.pokazPowiadomienie('blad', 'Nie zaladowano biblioteki wyboru koloru Pickr.');
            return;
        }

        const kolor = kolorGrupy(znajdzGrupe(idGrupy));
        przycisk.style.setProperty('--kolor-wybrany', kolor);

        const picker = window.Pickr.create({
            el: przycisk,
            theme: 'monolith',
            appClass: 'tabik-pickr-grupy',
            useAsButton: true,
            default: kolor,
            defaultRepresentation: 'HEXA',
            lockOpacity: false,
            comparison: true,
            closeOnScroll: true,
            autoReposition: true,
            swatches: paletaPickrGrupy,
            components: {
                palette: true,
                preview: true,
                opacity: true,
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
                'ui:dialog': 'Wybor koloru',
                'btn:toggle': 'Wybierz kolor naglowka',
                'btn:swatch': 'Probka koloru',
                'btn:last-color': 'Ostatni kolor',
                'btn:save': 'Zapisz',
                'btn:cancel': 'Anuluj',
                'btn:clear': 'Wyczysc'
            }
        });

        picker
            .on('show', () => menu.classList.add('ma-picker-otwarty'))
            .on('hide', () => menu.classList.remove('ma-picker-otwarty'))
            .on('change', (wybranyKolor) => {
                const kolorCss = normalizujKolor(wybranyKolor.toHEXA().toString());
                przycisk.style.setProperty('--kolor-wybrany', kolorCss);
                zastosujKolorGrupyLokalnie(idGrupy, kolorCss);
                zapiszKolorGrupyZDebounce(idGrupy, kolorCss);
            })
            .on('save', (wybranyKolor) => {
                const kolorCss = wybranyKolor ? normalizujKolor(wybranyKolor.toHEXA().toString()) : DOMYSLNY_KOLOR_GRUPY;
                przycisk.style.setProperty('--kolor-wybrany', kolorCss);
                zastosujKolorGrupyLokalnie(idGrupy, kolorCss);
                zapiszKolorGrupy(idGrupy, kolorCss, true).catch((blad) => {
                    api.pokazPowiadomienie('blad', blad.message || 'Nie udalo sie zapisac koloru grupy.');
                });
                picker.hide();
            })
            .on('clear', () => {
                przycisk.style.setProperty('--kolor-wybrany', DOMYSLNY_KOLOR_GRUPY);
                zastosujKolorGrupyLokalnie(idGrupy, DOMYSLNY_KOLOR_GRUPY);
                zapiszKolorGrupy(idGrupy, DOMYSLNY_KOLOR_GRUPY, true).catch((blad) => {
                    api.pokazPowiadomienie('blad', blad.message || 'Nie udalo sie zapisac koloru grupy.');
                });
            });

        stan.kolorPicker = { idGrupy: Number(idGrupy), picker };
    };

    const usunGrupe = async (idGrupy) => {
        if (!window.confirm('Usunac grupe? Zakladki zostana przeniesione do sekcji Bez grupy.')) return;
        const odpowiedz = await api.pobierzJson('api/zakladki/grupy/usun.php', { method: 'POST', body: JSON.stringify({ id: idGrupy }) });
        api.pokazPowiadomienie('sukces', odpowiedz.komunikat || 'Usunieto grupe.');
        await pobierzListe();
    };

    const importujJson = async (plik) => {
        const fd = new FormData();
        fd.set('plik', plik);

        const nazwa = (plik?.name || '').toLowerCase();
        const typ = (plik?.type || '').toLowerCase();
        const czyHtml = nazwa.endsWith('.html') || nazwa.endsWith('.htm') || typ.includes('text/html');
        const endpoint = czyHtml ? 'api/zakladki/import/html.php' : 'api/zakladki/import/json.php';

        const odpowiedz = await api.pobierzJson(endpoint, { method: 'POST', body: fd });
        api.pokazPowiadomienie('sukces', odpowiedz.komunikat || 'Zaimportowano backup.');
        await pobierzListe();
    };

    const wyczyscDropKategorie = () => {
        document.body.classList.remove('przenoszenie-grupy');
        document.querySelectorAll('.tab-kategorii.jest-celem-upuszczenia').forEach((el) => el.classList.remove('jest-celem-upuszczenia'));
        stan.przeciaganaGrupa = null;
    };

    const przeniesGrupeDoKategorii = async (idGrupy, idKategorii) => {
        if (!idGrupy || idGrupy <= 0) return;
        stan.przeniesionoGrupeDoKategorii = true;
        await api.pobierzJson('api/zakladki/grupy/przenies-kategoria.php', {
            method: 'POST',
            body: JSON.stringify({
                id: idGrupy,
                id_kategorii: idKategorii > 0 ? idKategorii : null,
            }),
        });
        await pobierzListe({ id_kategorii: idKategorii > 0 ? idKategorii : null });
    };

    const zapiszKolejnoscKategorii = async (kontener) => {
        const ids = [...kontener.querySelectorAll('.tab-kategorii[data-id-kategorii]')]
            .map((el) => Number(el.dataset.idKategorii))
            .filter((id) => id > 0);

        if (ids.length < 2) return;

        await api.pobierzJson('api/zakladki/kategorie/kolejnosc.php', {
            method: 'POST',
            body: JSON.stringify({ ids }),
        });
        await pobierzListe();
    };

    const uruchomSortowanieKategorii = () => {
        const kontener = els.pasekKategorii?.querySelector('[data-sort-kategorie="1"]');
        if (!kontener || kontener.children.length < 2) return;

        new window.Sortable(kontener, {
            animation: 150,
            draggable: '.tab-kategorii[data-id-kategorii]:not([data-id-kategorii="0"])',
            ghostClass: 'tab-kategorii-przeciagana',
            chosenClass: 'tab-kategorii-wybrana',
            onEnd: async (evt) => {
                if (evt.oldIndex === evt.newIndex) return;
                try {
                    await zapiszKolejnoscKategorii(kontener);
                } catch (blad) {
                    api.pokazPowiadomienie('blad', blad.message || 'Nie udalo sie zapisac kolejnosci kategorii.');
                    await pobierzListe();
                }
            },
        });
    };

    const podepnijDropNaKategorie = () => {
        if (!els.pasekKategorii || els.pasekKategorii.dataset.dropGrup === '1') return;
        els.pasekKategorii.dataset.dropGrup = '1';

        els.pasekKategorii.addEventListener('dragover', (e) => {
            if (!stan.przeciaganaGrupa) return;
            const cel = e.target.closest('[data-drop-kategoria="1"]');
            if (!cel) return;
            e.preventDefault();
            document.querySelectorAll('.tab-kategorii.jest-celem-upuszczenia').forEach((el) => {
                if (el !== cel) el.classList.remove('jest-celem-upuszczenia');
            });
            cel.classList.add('jest-celem-upuszczenia');
        });

        els.pasekKategorii.addEventListener('dragleave', (e) => {
            const cel = e.target.closest('[data-drop-kategoria="1"]');
            if (cel && !cel.contains(e.relatedTarget)) cel.classList.remove('jest-celem-upuszczenia');
        });

        els.pasekKategorii.addEventListener('drop', async (e) => {
            if (!stan.przeciaganaGrupa) return;
            const cel = e.target.closest('[data-drop-kategoria="1"]');
            if (!cel) return;
            e.preventDefault();
            const idGrupy = Number(stan.przeciaganaGrupa);
            const idKategorii = Number(cel.dataset.idKategorii || 0);
            wyczyscDropKategorie();

            try {
                await przeniesGrupeDoKategorii(idGrupy, idKategorii);
            } catch (blad) {
                stan.przeniesionoGrupeDoKategorii = false;
                api.pokazPowiadomienie('blad', blad.message || 'Nie udalo sie przeniesc grupy.');
                await pobierzListe();
            }
        });
    };

    const uruchomSortowanie = () => {
        if (typeof window.Sortable === 'undefined') return;
        podepnijDropNaKategorie();
        uruchomSortowanieKategorii();
        new window.Sortable(els.obszarKolumn, {
            animation: 150,
            draggable: '.kolumna-grupy-kompakt[data-id-grupy]:not(.kolumna-bez-grupy)',
            filter: '.karta-dodaj-grupe, input, textarea, select, button, a, [contenteditable="true"]',
            preventOnFilter: false,
            onStart: (evt) => {
                stan.przeniesionoGrupeDoKategorii = false;
                stan.przeciaganaGrupa = Number(evt.item.dataset.idGrupy || 0);
                document.body.classList.add('przenoszenie-grupy');
            },
            onEnd: async () => {
                const przeniesionoDoKategorii = stan.przeniesionoGrupeDoKategorii;
                window.setTimeout(wyczyscDropKategorie, 0);
                if (przeniesionoDoKategorii) {
                    stan.przeniesionoGrupeDoKategorii = false;
                    return;
                }
                const ids = [...els.obszarKolumn.querySelectorAll('.kolumna-grupy-kompakt[data-id-grupy]:not(.kolumna-bez-grupy)')]
                    .map((el) => Number(el.dataset.idGrupy))
                    .filter((id) => id > 0);
                if (ids.length) {
                    await api.pobierzJson('api/zakladki/grupy/kolejnosc.php', { method: 'POST', body: JSON.stringify({ ids }) });
                    await pobierzListe();
                }
            },
        });

        els.obszarKolumn.querySelectorAll('.lista-linkow-w-grupie').forEach((lista) => {
            new window.Sortable(lista, {
                group: 'zakladki-grupy',
                animation: 150,
                draggable: '.element-linku',
                handle: '.element-linku',
                filter: 'a, button',
                preventOnFilter: false,
                onEnd: async (evt) => {
                    const id = Number(evt.item.dataset.idZakladki);
                    const nowaLista = evt.to;
                    const staraLista = evt.from;
                    const idDocelowej = Number(nowaLista.dataset.idGrupy || 0);
                    const idZrodlowej = Number(staraLista.dataset.idGrupy || 0);
                    const kolejnoscDocelowa = [...nowaLista.querySelectorAll('.element-linku')].map((el) => Number(el.dataset.idZakladki));
                    const kolejnoscZrodlowa = [...staraLista.querySelectorAll('.element-linku')].map((el) => Number(el.dataset.idZakladki));
                    await api.pobierzJson('api/zakladki/przenies.php', {
                        method: 'POST',
                        body: JSON.stringify({
                            id,
                            id_grupy_docelowej: idDocelowej > 0 ? idDocelowej : null,
                            id_grupy_zrodlowej: idZrodlowej > 0 ? idZrodlowej : null,
                            kolejnosc_docelowa: kolejnoscDocelowa,
                            kolejnosc_zrodlowa: kolejnoscZrodlowa,
                        }),
                    });
                    await pobierzListe();
                },
            });
        });
    };

    const debounce = (fn, wait = 250) => {
        let timer;
        return (...args) => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => fn(...args), wait);
        };
    };

    const obsluzAkcje = async (e) => {
        const przycisk = e.target.closest('[data-akcja]');
        if (!przycisk) return;
        const akcja = przycisk.dataset.akcja;
        try {
            switch (akcja) {
                case 'dodaj-zakladke':
                    otworzModalZakladki();
                    break;
                case 'dodaj-zakladke-do-grupy':
                    otworzModalZakladki('dodaj', null, przycisk.dataset.idGrupy);
                    zamknijMenu();
                    break;
                case 'dodaj-grupe':
                    otworzModalGrupy();
                    zamknijMenu();
                    break;
                case 'dodaj-kategorie':
                    pokazModal(els.modalKategoria);
                    break;
                case 'import-json':
                    if (!els.fileInput) {
                        console.error('Element fileInput nie zostal znaleziony.');
                        return;
                    }
                    els.fileInput.click();
                    break;
                case 'filtr-kategoria': {
                    const suroweId = przycisk.dataset.idKategorii;
                    const idKategorii = suroweId === '' ? null : Number(suroweId);
                    await pobierzListe({ id_kategorii: idKategorii });
                    if (idKategorii !== null && !Number.isNaN(idKategorii)) {
                        await zapamietajOstaniąKategorię(idKategorii);
                    }
                    break;
                }
                case 'przelacz-menu-grupy': {
                    const idGrupy = przycisk.dataset.idGrupy;
                    const menu = document.getElementById(`menu-grupy-${idGrupy}`);
                    const toSamo = stan.aktywneMenu === idGrupy;
                    zamknijMenu();
                    if (!toSamo && menu) {
                        menu.classList.remove('ukryte');
                        stan.aktywneMenu = idGrupy;
                        przygotujPickerKoloruGrupy(Number(idGrupy));
                    }
                    break;
                }
                case 'otworz-wszystkie':
                    otworzWszystkie(Number(przycisk.dataset.idGrupy));
                    zamknijMenu();
                    break;
                case 'usun-grupe':
                    await usunGrupe(Number(przycisk.dataset.idGrupy));
                    zamknijMenu();
                    break;
                case 'przelacz-ulubiona':
                    await przelaczUlubiona(Number(przycisk.dataset.idZakladki));
                    break;
                case 'edytuj-zakladke':
                    otworzModalZakladki('edytuj', znajdzZakladke(Number(przycisk.dataset.idZakladki)));
                    break;
                case 'usun-zakladke':
                    await usunZakladke(Number(przycisk.dataset.idZakladki));
                    break;
            }
        } catch (blad) {
            api.pokazPowiadomienie('blad', blad.message || 'Wystapil blad.');
        }
    };


    const obsluzPrawyKlikNaglowkaGrupy = (e) => {
        const naglowek = e.target.closest('.naglowek-grupy-kompakt[data-edytuj-grupe-prawym="1"]');
        if (!naglowek || !els.obszarKolumn?.contains(naglowek)) return;
        if (e.target.closest('.akcje-grupy-kompakt, .menu-grupy, button, a, input, textarea, select, [contenteditable="true"]')) return;

        const kolumna = naglowek.closest('.kolumna-grupy-kompakt[data-id-grupy]');
        const idGrupy = Number(kolumna?.dataset.idGrupy || 0);
        if (!idGrupy) return;

        const grupa = znajdzGrupe(idGrupy);
        if (!grupa) return;

        e.preventDefault();
        zamknijMenu();
        rozpocznijEdycjeNazwyGrupy(idGrupy);
    };

    const podpinijSzukaj = () => {
        const handler = debounce(async (wartosc) => {
            try {
                await pobierzListe({ q: wartosc });
            } catch (blad) {
                api.pokazPowiadomienie('blad', blad.message || 'Nie udalo sie wyszukac.');
            }
        }, 260);

        [els.searchTop, els.searchPanel].forEach((pole) => {
            if (!pole) return;
            pole.addEventListener('input', (e) => {
                const wartosc = e.target.value;
                aktualizujPolaSzukania(wartosc);
                handler(wartosc);
            });
        });
    };

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.akcje-grupy-kompakt, .pcr-app')) zamknijMenu();
    });

    els.obszarKolumn?.addEventListener('contextmenu', obsluzPrawyKlikNaglowkaGrupy);


    document.getElementById('przycisk-importu-zakladek')?.addEventListener('click', () => {
        if (!els.fileInput) {
            api.pokazPowiadomienie('blad', 'Brak pola importu pliku.');
            return;
        }
        els.fileInput.click();
    });

    document.addEventListener('click', obsluzAkcje);
    els.formZakladka?.addEventListener('submit', (e) => zapiszZakladke(e).catch((blad) => api.pokazPowiadomienie('blad', blad.message || 'Blad zapisu.')));
    els.formGrupa?.addEventListener('submit', (e) => zapiszGrupe(e).catch((blad) => api.pokazPowiadomienie('blad', blad.message || 'Blad zapisu grupy.')));
    els.formKategoria?.addEventListener('submit', (e) => zapiszKategorie(e).catch((blad) => api.pokazPowiadomienie('blad', blad.message || 'Blad zapisu kategorii.')));
    els.fileInput?.addEventListener('change', (e) => {
        const plik = e.target.files?.[0];
        if (!plik) return;
        importujJson(plik)
            .catch((blad) => api.pokazPowiadomienie('blad', blad.message || 'Nie udalo sie zaimportowac pliku.'))
            .finally(() => { e.target.value = ''; });
    });

    podpinijSzukaj();
    renderuj();
})();
