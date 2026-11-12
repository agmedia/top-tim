# Sidrene cijene, XML cjenici, jamstvo i povrati

## Što je dodano

- Admin **Sidrene cijene / Cjenici**: pretraga po nazivu, šifri ili ID-u, statusi za provjeru/potvrđeno, uređivanje cijene i datuma, obavezan razlog/izvor, povijest izmjena, CSV izvoz/uvoz s probnom provjerom i ručna dnevna objava. Pristup imaju aktivni administratori i superadministratori.
- Potvrđena sidrena cijena s datumom prikazuje se na karticama (uključujući filtere i povezane proizvode), stranici proizvoda, rasprodanim proizvodima i prijedlozima pretrage. Dohvat koristi zajedničku predmemoriju, bez zasebnog SQL upita za svaku karticu. Sidrena cijena ne sudjeluje u obračunu narudžbe ili količinskih popusta.
- U **Sidrene cijene / Cjenici → Prikaz sidrenih cijena** prekidač **Prikazuj sidrene cijene na webshopu** i gumb **Spremi prikaz** uključuju ili isključuju prikaz na svim navedenim mjestima nakon osvježavanja stranice. Postavka se sprema u postojeću tablicu `settings`, bez nove migracije, i čita jednom po zahtjevu. Zadano je uključena. Isključivanje čuva cijene, potvrde, revizije i XML arhivu; automatska izrada XML cjenika nastavlja raditi.
- Footer **Uvjeti kupnje → Cjenici** vodi na `/cjenici`. Najnoviji strojno čitljiv cjenik ima stalnu adresu `/cjenici/najnoviji.xml`, a arhivirani dokument `/cjenici/{id}.xml`.
- Službena hrvatska obavijest o zakonskom jamstvu iz postojećeg lokalnog Salonatech modula, neizmijenjen SVG, otvara se u modalu iz footera. Dostupna je i na `/zakonsko-jamstvo`, s poveznicom sa stranice proizvoda i u potvrdi narudžbe.
- Kraći obrazac `/povrat-proizvoda` koristi postojeće spremanje, statusnu obradu i potvrde e-poštom za jednostrane raskide. Nema nove tablice za povrate. Dodatni podaci su sklopljeni i neobavezni; reklamacije su jasno odvojene kontaktom. Izvorni obrazac i njegova adresa ostaju dostupni.

## Instalacija

Nova migracija dodaje samo `anchor_prices`, `anchor_price_revisions` i `price_list_publications`. Ne mijenja postojeće proizvode, cijene, popuste, kupce ili narudžbe i ne popunjava povijesne cijene nagađanjem. Testovi provjeravaju očuvanje postojećih podataka i sheme.

Nakon objave koda primijeniti samo ovu migraciju, ne cijeli stari skup migracija:

```sh
php artisan migrate --path=database/migrations/2026_11_12_190000_create_anchor_price_tables.php --force
php artisan config:cache
php artisan view:cache
php artisan schedule:list --timezone=Europe/Zagreb
```

`public/js/cart.js` je izgrađen i uključen u izmjene. Build je provjeren na lokalnom Nodeu 21.7.3; stari zadani Node u PATH-u ne podržava postojeće frontend ovisnosti.

Laravel scheduler mora se pokretati svake minute, preko već postojećeg cron zadatka projekta ako je postavljen:

```cron
* * * * * cd /home/toptimco/public_html && php artisan schedule:run >> /dev/null 2>&1
```

Nemojte dodavati drugi cron ako isti već postoji. Zadana dnevna objava je u 07:00 u zoni Europe/Zagreb. `PRICE_LISTS_ENABLED=false` isključuje automatsku objavu; promjena zahtijeva obnovu config cachea. Ručni poziv:

```sh
php artisan prices:publish
```

Izvršavanje objave treba pravo pisanja u `storage/app/price-lists`. Datoteke se izrađuju u privremenoj putanji i postaju javno dostupne tek kada je cijeli dokument dovršen i zapisan registar s SHA-256. Javne rute ne traže prijavu ni CAPTCHA-u. Dnevna objava je nepromjenjiva; ponovni poziv istog dana vraća postojeći dokument, a sljedeći dan dobiva novu snimku. Datoteke i revizije čuvaju se, a javna arhiva prikazuje objave stare najviše 30 dana.

## Unos stvarnih sidrenih cijena

U adminu preuzeti CSV. Za proizvode bez zapisa izvoz predlaže današnju redovnu cijenu i datum 10.09.2026., ali ostavlja status `pending`. To nije dokaz povijesne cijene. Prije potvrde provjeriti iznos i referentni datum prema evidenciji; za kasnije uvedene proizvode upisati cijenu i datum prve stvarne prodajne ponude. Oba iznosa su maloprodajni EUR s PDV-om, kao postojeće cijene Top Tima.

CSV stupci: `product_id;sku;name;price;reference_date;status`. Obvezni su `product_id`, `price`, `reference_date`, `status`; `sku` se provjerava ako je naveden. Datum je `YYYY-MM-DD`; status je `pending` ili `confirmed`. Podržani su separator `;` ili `,`, decimalna točka ili decimalni zarez, najviše 5 MB i 10.000 redaka. Uvoz traži zajednički izvor/razlog izmjene. Najprije koristiti **Samo provjera**, zatim ponoviti bez te kvačice. Neispravan ili ponovljen proizvod odbija cijeli uvoz.

Na frontu se prikazuju samo potvrđeni zapisi. Promjena redovne cijene ili količinskog popusta ne mijenja sidrenu cijenu. Novi artikli također zahtijevaju potvrđen zapis; nedostajući zapisi vidljivi su u adminu.

Cjenik obuhvaća aktivne proizvode s pozitivnom prodajnom cijenom, uključujući privremeno nedostupne. Objavu zaustavlja proizvod bez potvrđene sidrene cijene, naziva ili šifre. Neispravan/nedostajući GTIN daje prazno polje barkoda i upozorenje s brojem takvih proizvoda. Dopuniti EAN iz stvarne dobavljačeve evidencije.

Cjenik uključuje osnovnu cijenu proizvoda po komadu, javne akcije, sidrenu cijenu i datum, EAN, marku i raspoloživost. Koristi postojeće obračunavanje javnih akcija; privatni popusti, grupe korisnika i kuponi ne utječu na XML ni pri ručnoj objavi iz prijavljenog admina. Doplata pojedine varijante nije zaseban redak cjenika; sidrene cijene vode se na razini proizvoda, kao u preuzetom modulu.

## Provjere i ograničenja ove sesije

Dorada prekidača prikaza: **35 PHP testova / 379 provjera** prolazi, uključujući trajno spremanje postavke, isključivanje/uključivanje nakon učitanih cijena, očuvanje XML objave i cijena, validaciju te zabranu promjena gostima, kupcima i urednicima. Provjera je otkrila nepotpunu testnu shemu korisničkih grupa; fixture je usklađen sa stvarnom shemom, bez promjene baze aplikacije.

Konačna provjera: **165 PHP testova / 1198 provjera** prošlo je na PHP-u 8.2. Pokriveni su migracija, ovlasti, revizije, CSV, javni prikaz, XML sadržaj, neuspješna objava, nepromjenjivost arhive, provjera kontrolnog zbroja, povrati i regresije količinskih popusta, košarice, kataloga i narudžbi. PHP provjere bilježe 10 postojećih PHPUnit upozorenja o zastarjeloj konfiguraciji.

Frontend build, provjera PHP sintakse i kompilacija Blade predložaka prolaze. Prolazi **5 JavaScript testova**, uključujući prikaz sidrene cijene u izgrađenom Vue bundleu tijekom promjene količine i varijante. Postojeća dva `runtime.test.cjs` testa u ovom okruženju ne mogu otvoriti lokalni TCP port (`EPERM`). Testovi filtera, količinskih popusta i admin razina koriste jsdom bez poslužitelja.

Ova sesija nema pristup lokalnom MySQL-u (`Operation not permitted`), pa migracija nije primijenjena na stvarnu bazu i nije napravljen stvarni cjenik. Browser pregled lokalnog HTTPS-a blokira istekli certifikat (`ERR_CERT_DATE_INVALID`). Funkcionalne provjere koriste izoliranu SQLite bazu i lažnu pohranu; e-pošta nije slana stvarnim kupcima. Produkcija i njezin cron nisu mijenjani.

## Izvori i prethodno rješenje

Prilagođeno iz lokalnog `salonatech-codex-footer-cjenici` (sidrene cijene i arhiva) te `salonatech-codex-garan-sidrene` (službena obavijest jamstva). Hrvatski SVG je identičan izvorniku, SHA-256 `a22432ff287ae772fd7f1bc7198dce26cbcfba3b1c8c6fa207189c1163e2612e`.

- [NN 101/2026 – objava cjenika](https://narodne-novine.nn.hr/clanci/sluzbeni/2026_09_101_1213.html)
- [NN 101/2026 – dodatna cijena](https://narodne-novine.nn.hr/clanci/sluzbeni/2026_09_101_1212.html)
- [Your Europe – zakonsko jamstvo](https://europa.eu/youreurope/jamstva_hr)

Datum i sadržaj propisa provjereni su tijekom implementacije 12.11.2026.; konfiguracija rasporeda izdvojena je radi budućih promjena. Dokument opisuje implementaciju za webshop, ne provjeru svih poslovnih obveza drugih prodajnih mjesta.
