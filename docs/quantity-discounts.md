# Količinski popusti po korisniku ili grupi i brendu

Administratori i superadministratori uređuju pravila u **Količinski popusti** (`/admin/discounts`). U jednom obrascu odabiru se korisnik ili grupa i brend, a gumbom **Dodaj razinu** dodaju se pragovi, primjerice 5 komada → 5 %, 10 komada → 10 %. Svaka razina ima vlastiti status. Spremanje je zajedničko i odvija se u jednoj transakciji. Postojeća pravila za isti odabir prikazuju se zajedno, bez nove migracije.

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

Migracija je provjerena na izoliranoj SQLite bazi, a korisnik je potvrdio uspješno izvršavanje na lokalnom MySQL-u. Status migracije na produkciji provjerava se odvojeno. Dorada za više razina koristi tu postojeću tablicu i ne zahtijeva migraciju. Frontend datoteke `public/js/cart.js` i `public/js/filter.js` već su izgrađene.

## Provjere

`QuantityDiscountTest` provjerava ovlasti, unos i uređivanje pravila, izolaciju korisnika, aktivnost grupe, promjenu članstva, pragove, druge brendove, doplate za varijante, košaricu, spremljenu cijenu narudžbe, postojeće akcije, 100 % popusta i očuvanje postojećih podataka migracijom. JavaScript provjere koriste izgrađeni frontend za cijene na proizvodu i u košarici.

## Objava dorade s više razina

Za ovu doradu **ne pokretati migracije**. Nakon pusha i preuzimanja koda dovoljno je obnoviti predloške. Nova skripta za dodavanje redaka je `public/js/back/discount-tiers.js` i učitava se izravno, bez novog frontend builda.

Lokalno, iz projekta, ove putanje izostavljaju pripremljene slike i nepovezane izmjene:

```sh
git add app/Http/Controllers/Back/DiscountController.php resources/views/back/discount public/js/back/discount-tiers.js tests/Feature/QuantityDiscountTest.php tests/JavaScript/discount-tiers.test.cjs phpunit.xml docs/quantity-discounts.md &&
git commit --only -m "Edit quantity discount tiers in one form" -- app/Http/Controllers/Back/DiscountController.php resources/views/back/discount public/js/back/discount-tiers.js tests/Feature/QuantityDiscountTest.php tests/JavaScript/discount-tiers.test.cjs phpunit.xml docs/quantity-discounts.md &&
git push origin HEAD:master
```

Na serveru nakon uspješnog pusha i uz već instaliranu početnu tablicu:

```sh
cd /home/toptimco/public_html &&
git pull --ff-only origin master &&
php artisan view:cache
```
