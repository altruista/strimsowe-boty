## Moje strimsowe boty

Gotowy "framework" do tworzenia botów.

Po poprawnej instalacji wystarczy operować tylko na dwóch katalogach aby skrypt działał:

### modules/strimy/
Strimy. Klasa `AbstractStrim` musi:
- być nazwą strimu
- posiadać metodę `getListings` zwracającą tablicę z obiektami `Listing`
- posiadać zmienną `$bot_name` (ten bot będzie dodawał treści do strimu)

### modules/boty/
Boty. Klasa `AbstractBot` musi:
- być nazwą bota (nazwa użytkownika strims)
- posiadać zmienną `$password` z hasłem użytkownika

### Instalacja
1. Do działania potrzebny jest plik konfiguracja bazy `configs/database.php`:
`return array('host'=>.., 'user'=>.., 'password'=>.., 'database'=>..);`
2. Trzeba stworzyć bazę danych korzystając z pliku `database.sql`
3. Katalogi w `tmp/` powinny mieć prawa do zapisu
4. Należy ustawić crontab dla dwóch skryptów (patrz niżej)

### Skrypty
- `populate_queue.php` mieli strimy i dodaje linki do bazy
- `post_queue.php` dodaje linki do Strims.pl

#### Licencja:
GPLv2
