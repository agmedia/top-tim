# Količinski popusti po korisniku ili grupi i brendu

Administratori i superadministratori uređuju pravila u **Količinski popusti** (`/admin/discounts`). Svako pravilo ima korisnika ili grupu, brend, minimalni broj komada, postotak i status. Za više pragova dodaje se više pravila.

Količina se zbraja po brendu kroz sve proizvode i varijante u košarici. Aktivna osobna pravila i pravila aktivne grupe daju najviši postotak čiji je prag dosegnut. Postotak se računa na redovnu cijenu s doplatom za varijantu. Zadržava se povoljnija postojeća akcijska cijena; popusti na isti artikl ne zbrajaju se. Postojeće pogodnosti na ukupnu košaricu ostaju zasebna pravila.

Stranica proizvoda prikazuje dostupne pragove i cijenu za odabranu količinu zajedno s komadima već u košarici. Košarica ponovno računa cijene na serveru pri dohvaćanju, promjeni i uklanjanju stavaka. Narudžba pohranjuje cijenu iz tog obračuna. Pravila se učitavaju jednom po HTTP zahtjevu i ne spremaju se u zajednički cache korisničkih cijena.

## Migracija

`2026_11_12_180000_create_customer_discounts_table.php` kreira isključivo novu tablicu `customer_discounts` i njezine indekse. Ne mijenja postojeće tablice niti njihove retke i nema vanjske ključeve s kaskadnim brisanjem. Test uspoređuje strukturu i podatke postojećih tablica prije i nakon migracije. Povrat migracije briše novu tablicu i njezina pravila; nije potreban za redovnu objavu.

Iz korijena aplikacije pokrenuti **samo ovu datoteku**, bez drugih migracija na čekanju:

```sh
php artisan migrate --path=database/migrations/2026_11_12_180000_create_customer_discounts_table.php --force
php artisan config:cache
php artisan view:cache
```

Migracija je provjerena na izoliranoj SQLite bazi. Pristup lokalnom MySQL-u iz radnog okruženja blokiran je, pa migracija na lokalnoj ili produkcijskoj MySQL bazi još nije izvršena. Frontend datoteke `public/js/cart.js` i `public/js/filter.js` već su izgrađene.

## Provjere

`QuantityDiscountTest` provjerava ovlasti, unos i uređivanje pravila, izolaciju korisnika, aktivnost grupe, promjenu članstva, pragove, druge brendove, doplate za varijante, košaricu, spremljenu cijenu narudžbe, postojeće akcije, 100 % popusta i očuvanje postojećih podataka migracijom. JavaScript provjere koriste izgrađeni frontend za cijene na proizvodu i u košarici.

## Objava preko Gita

Lokalno, iz projekta. Izričiti odabir putanja izostavlja pripremljene slike i druge izgrađene datoteke koje nisu dio ove izmjene.

```sh
git add app resources routes tests docs/quantity-discounts.md database/migrations/2026_11_12_180000_create_customer_discounts_table.php public/js/cart.js public/js/filter.js &&
git commit --only -m "Add customer quantity discounts by brand" -- app resources routes tests docs/quantity-discounts.md database/migrations/2026_11_12_180000_create_customer_discounts_table.php public/js/cart.js public/js/filter.js &&
git push origin HEAD:master
```

Na serveru nakon uspješnog pusha:

```sh
cd /home/toptimco/public_html &&
git pull --ff-only origin master &&
php artisan migrate --path=database/migrations/2026_11_12_180000_create_customer_discounts_table.php --force &&
php artisan config:cache &&
php artisan view:cache
```
