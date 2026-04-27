# Tabik — kontekst projektu i twarde zasady

## Cel projektu

Tabik to prosty system PHP/MySQL do zarządzania zakładkami. Ma pozostać lekki, przejrzysty i „po fakturowniowemu”: bez nadmiarowych warstw, bez frameworkowej komplikacji, z czytelnymi plikami `.php` pełniącymi rolę widoków i prostych punktów wejścia.

## Model biznesowy

- Użytkownik loguje się do systemu.
- Użytkownik ma kategorie.
- W kategoriach są grupy.
- W grupach są zakładki.
- Zakładki, grupy i kategorie mogą mieć kolejność/przesuwanie drag & drop.
- Aktualna baza danych projektu nazywa się `tabik`.

## Architektura

- Preferuj prostą strukturę katalogów według funkcji.
- Nie dodawaj nowych warstw abstrakcji, serwisów, kontenerów, routerów ani pseudoframeworka bez konieczności.
- Pliki `.php` mają być czytelne i praktyczne: konfiguracja, logika wejściowa, prosty widok HTML.
- Wspólne elementy przenoś do prostych include/partiali, ale nie przesadzaj z abstrakcją.
- Przed refaktoryzacją zawsze sprawdź istniejące zależności przez wyszukiwanie użyć.

## Bezpieczeństwo

- Dla SQL używaj prepared statements.
- Nie ufaj danym z `$_GET`, `$_POST`, cookies ani sesji bez walidacji.
- Zachowuj kontrolę dostępu użytkownika: dane zakładek, grup i kategorii muszą być powiązane z właściwym `user_id`.
- Przy akcjach modyfikujących dane stosuj walidację serwerową i, jeśli projekt już ma mechanizm CSRF, używaj go konsekwentnie.
- Nie wypisuj surowych błędów SQL użytkownikowi.
- Nie zapisuj haseł jawnie; hasła mają używać `password_hash` / `password_verify`.

## Zasady zmian

- Nie zostawiaj starych nazw bazy, martwych endpointów, duplikatów funkcji ani nieużywanego CSS.
- Przy zmianie bazy znajdź wszystkie odwołania do nazw tabel, kolumn i nazwy bazy.
- Przy zmianie UI usuń też niepotrzebny CSS, JS, linki nawigacji i martwe klasy.
- Po każdej zmianie wskaż krótko, co trzeba ręcznie sprawdzić w przeglądarce.