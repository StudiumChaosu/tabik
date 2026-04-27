# Prompt do analizy panel.css

Wykonaj analogiczne zadanie analizy CSS dla pliku `panel.css` jak zrobiliśmy dla `glowny.css`.

## Zadanie

Użyj istniejącego skryptu `analyze_css_classes.py` do przeanalizowania pliku `public_html/assets/css/panel.css` i zidentyfikowania nieużywanych klas CSS.

## Kroki wykonania

1. **Uruchom analizę** na pliku `panel.css`:
   ```bash
   python analyze_css_classes.py --css public_html/assets/css/panel.css --root public_html --output css_panel_analysis
   ```

2. **Przejrzyj wygenerowane raporty:**
   - `css_panel_analysis/summary_report.txt` - raport tekstowy
   - `css_panel_analysis/detailed_report.json` - szczegółowy raport JSON

3. **Przeanalizuj wyniki** i zidentyfikuj:
   - Które klasy są używane w projekcie
   - Które klasy są nieużywane i mogą być usunięte
   - Które klasy mogą być fałszywie pozytywne (wartości CSS zamiast klas)

4. **Stwórz backup** przed jakimikolwiek zmianami:
   ```bash
   copy public_html\assets\css\panel.css public_html\assets\css\panel.css.backup
   ```

5. **Przedstaw podsumowanie** zawierające:
   - Liczbę znalezionych klas CSS
   - Liczbę używanych klas
   - Liczbę nieużywanych klas
   - Procent nieużywanych klas
   - Listę nieużywanych klas pogrupowanych tematycznie
   - Potencjalne korzyści z czyszczenia

## Oczekiwany format raportu

```
✅ Zadanie zakończone sukcesem!

Wykonane działania:
1. Uruchomiono analizę na pliku panel.css
2. Przeanalizowano X plików w katalogu public_html
3. Wygenerowano raporty w css_panel_analysis/
4. Utworzono backup: panel.css.backup

Wyniki analizy:
- Przed: X klas CSS w panel.css
- Używane: X klas CSS (X%)
- Nieużywane: X klas CSS (X%)

Nieużywane klasy do ręcznego usunięcia:
[Lista pogrupowana tematycznie]

Korzyści:
- Potencjalna redukcja rozmiaru pliku o ~X%
- Łatwiejsze utrzymanie kodu
- Proces zajął ~X sekund

Bezpieczeństwo:
- Backup zapisany w panel.css.backup
```

## Uwagi

- **NIE używaj** flagi `--clean` do automatycznego czyszczenia - może to uszkodzić złożone selektory CSS
- Zwróć uwagę na klasy używane dynamicznie w JavaScript (np. `.jest-aktywny`, `.jest-zwiniety`, `.jest-ukryty`)
- Sprawdź czy klasy nie są używane w plikach JS do manipulacji DOM
- Fałszywe pozytywne (wartości CSS jak `.8rem`) należy zignorować

## Kontekst projektu

Plik `panel.css` zawiera style dla:
- Layout aplikacji (`.powloka-aplikacji`, `.obszar-aplikacji`)
- Panel boczny (`.panel-boczny`, `.nawigacja-glowna`)
- Panel kontekstowy prawy (`.panel-kontekstowy`)
- Górny pasek (`.gorny-pasek`)
- Sekcje i karty panelu (`.sekcja-panelowa`, `.karta-statystyki`)
- Profil użytkownika (`.siatka-profilu-dwie-kolumny`)
- Stany paneli (`.jest-zwiniety`, `.jest-ukryty`, `.jest-widoczny`)
- Responsywność (@media queries)

## Pliki do przeszukania

Skrypt automatycznie przeszuka:
- Wszystkie pliki `.php` w `public_html/`
- Wszystkie pliki `.js` w `public_html/assets/js/`
- Wszystkie pliki `.css` w `public_html/assets/css/`
- Pliki w `public_html/modules/`
- Pliki w `public_html/includes/`

## Oczekiwany czas wykonania

~3-5 sekund dla pełnej analizy projektu.
