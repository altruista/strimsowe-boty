## Moje strimsowe boty

Gotowy framework do tworzenia botów.

### modules/strimy/
Strimy. Klasa `AbstractStrim` musi:
- być nazwą strimy
- metodę `getListings` zwracającą tablicę z obiektamy `Listring`
- zmienna `$bot_name` musi zostać ustawiona

### modules/boty/
Boty. Klasa `AbstractBot` musi:
- być nazwą bota (nazwa użytkownika strims)
- posiadać zmienną `$password` z hasłem użytkownika

### Konfiguracja bazy
Do działania potrzebna jest plik konfiguracja bazy `configs/database.php':

`return array('host'=>..,'user'=>..,'password'=>..,'database'=>..);`

### Skrypty
- `populate_queue.php` mieli strimy i dodaje linki do bazy
- `post_queue.php` dodaje linki do Strims.pl

#### Licencja:
GPLv2
