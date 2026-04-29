(() => {
    const api = window.aplikacja;
    if (!api?.utworzPickrKoloruUzytkownika) return;

    const init = () => {
        document.querySelectorAll('[data-widok2-kolor-pickr]').forEach((przycisk) => {
            api.utworzPickrKoloruUzytkownika(przycisk, {
                obszar: 'idkolor_prom',
                cssVar: '--kolor-tla-widok2',
                domyslny: '#f5f7fb',
                appClass: 'tabik-pickr-tlo',
                komunikatSukces: 'Kolor Widoku 2 zostal zapisany.',
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
