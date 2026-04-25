// ===================================
// MODUL: FORMULARZE
// OBSZAR: public/zasoby/js/formularze.js
// OPIS: Logika interfejsu i obsluga zdarzen po stronie przegladarki.
// ===================================

(() => {
    document.addEventListener('click', (zdarzenie) => {
        const przycisk = zdarzenie.target.closest('[data-przelacz-haslo]');
        if (!przycisk) return;

        const selektor = przycisk.getAttribute('data-przelacz-haslo');
        const pole = document.querySelector(selektor);

        if (!pole) return;

        pole.type = pole.type === 'password' ? 'text' : 'password';
        const ikona = przycisk.querySelector('i');

        if (ikona) {
            ikona.className = pole.type === 'password' ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
        }
    });

    document.addEventListener('submit', (zdarzenie) => {
        const formularz = zdarzenie.target.closest('form');
        if (!formularz) return;

        const przycisk = formularz.querySelector('button[type="submit"]');
        if (!przycisk) return;

        if (przycisk.dataset.wTrakcie === '1') {
            zdarzenie.preventDefault();
            return;
        }

        przycisk.dataset.wTrakcie = '1';
        przycisk.disabled = true;

        window.setTimeout(() => {
            przycisk.dataset.wTrakcie = '0';
            przycisk.disabled = false;
        }, 2500);
    });
})();
