# Bazne cijene, XML cjenici, jamstvo i povrati

## Što je dodano

- Admin **Bazne cijene / Cjenici**: pretraga po nazivu, šifri ili ID-u, statusi za provjeru/potvrđeno, uređivanje cijene i datuma, obavezan razlog/izvor, povijest izmjena, CSV izvoz/uvoz s probnom provjerom i ručna dnevna objava. Pristup imaju aktivni administratori i superadministratori.
- Potvrđena sidrena cijena s datumom prikazuje se na karticama (uključujući filtere i povezane proizvode), stranici proizvoda, rasprodanim proizvodima i prijedlozima pretrage. Dohvat koristi zajedničku predmemoriju, bez zasebnog SQL upita za svaku karticu. Sidrena cijena ne sudjeluje u obračunu narudžbe ili količinskih popusta.
- Footer **Uvjeti kupnje → Cjenici** vodi na `/cjenici`. Najnoviji strojno čitljiv cjenik ima stalnu adresu `/cjenici/najnoviji.xml`, a arhivirani dokument `/cjenici/{id}.xml`.
- Službena hrvatska obavijest o zakonskom jamstvu iz postojećeg lokalnog Salonatech modula, neizmijenjen SVG, otvara se u modalu iz footera. Dostupna je i na `/zakonsko-jamstvo`, s poveznicom sa stranice proizvoda i u potvrdi narudžbe.
- Kraći obrazac `/povrat-proizvoda` koristi postojeće spremanje, statusnu obradu i potvrde e-poštom za jednostrane raskide. Nema nove tablice za povrate. Dodatni podaci su sklopljeni i neobavezni; reklamacije su jasno odvojene kontaktom. Izvorni obrazac i njegova adresa ostaju dostupni.

## Instalacija

Na Top Timu koji već ima tablice baznih cijena primijenite migraciju za verzioniranje, zatim osvježite konfiguraciju, predloške i javni XML.

```sh
php artisan migrate --path=database/migrations/2026_11_18_080000_version_webshop_price_lists.php --force
php artisan config:cache
php artisan view:cache
php artisan prices:publish
php artisan schedule:list --timezone=Europe/Zagreb
```

Za novu instalaciju prije toga primijenite izvornu migraciju `2026_11_12_190000_create_anchor_price_tables.php`. Objavljene datoteke zadržavaju svoje ID-eve. Migracija postojećoj verziji dodjeljuje trenutak stvarne sljedeće objave kao datum zamjene. Aktualna verzija nema datum zamjene.

Laravel scheduler mora se pokretati svake minute, preko već postojećeg cron zadatka projekta ako je postavljen:

```cron
* * * * * cd /home/toptimco/public_html && php artisan schedule:run >> /dev/null 2>&1
```

Postojeći scheduler provjerava promjene svakih pet minuta. Vrijeme za naziv datoteke računa u zoni Europe/Zagreb. Administratorski gumb **Provjeri i objavi izmjene** pokreće istu provjeru kao `prices:publish`. Trenutačno važeća verzija dostupna je neovisno o svojoj starosti. Prethodne javne verzije dostupne su 30 dana od zamjene.

## Provjera podataka i unos cijena

U **Bazne cijene / Cjenici** provjerite kategoriju, stvarni datum prve ponude, akcije na referentni datum i izvor potvrde. Za sportsku opremu u ponudi 10. rujna 2026. vrijedi tadašnja maloprodajna cijena, uz posebno pravilo za tadašnji popust. Za kasnije artikle provjerite cijenu prve ponude. Za kategorije iz čl. 3. st. 1. podst. 1. i 2. pravilnika 1583 vrijedi 2. svibnja 2025. ili kasniji dan prve ponude, uz pravilo o najnižoj cijeni prije početka popusta kada je primjenjivo. Kod poznate prve ponude spremite datum i odaberite skupinu radi provjere referentnog datuma. Evidencija najnižih cijena zasebna je od potvrđene bazne cijene.

Masovni unos zadržava CSV stupce `product_id;sku;name;price;reference_date;status`, probnu provjeru i bilješku o izvoru. Za proizvod bez postojeće evidencije izvoz ostavlja prazan iznos i datum. U uređivanju proizvoda možete spremiti neto količinu u odabranoj jedinici i zaseban barkod. Za artikl od 250 ml upišite 0,25 l. Neto količina odnosi se na sadržaj pakiranja. Redovna zaliha vodi se zasebno.

XML izvozi javne cijene i akcije, redom naziv, šifru, marku, neto količinu, jedinicu mjere, maloprodajnu cijenu s oznakom akcije, cijenu po jedinici, barkod, EAN i baznu cijenu. Dodatni podaci iz prethodnog formata slijede iza bazne cijene. Za artikl s opcijama redak predstavlja prodajnu varijantu. U uređivanju bazne cijene otvorite **Bazne cijene i kodovi varijanti**. Varijante koje dijele cijenu mogu koristiti evidenciju proizvoda. Ako varijanta ima doplatu ili posebnu povijesnu cijenu, unesite stvarni povijesni iznos i datum. Identitet varijante vezan je uz proizvod i kombinaciju opcija kako bi preživio redovno ponovno spremanje proizvoda.

Ako provjera prijavi nepotpunu ponudu, izlaz naredbe i greška u administraciji navode šifre za provjeru. Zadnji dovršeni XML ostaje dostupan tijekom ispravka. U javnom XML-u neobjavljeni osobni popusti i kuponi ne utječu na maloprodajnu cijenu.

## Izvori i prethodno rješenje

Prilagođeno iz lokalnog `salonatech-codex-footer-cjenici` (sidrene cijene i arhiva) te `salonatech-codex-garan-sidrene` (službena obavijest jamstva). Hrvatski SVG je identičan izvorniku, SHA-256 `a22432ff287ae772fd7f1bc7198dce26cbcfba3b1c8c6fa207189c1163e2612e`.

- [NN 132/2026, br. 1583, isticanje bazne cijene](https://narodne-novine.nn.hr/clanci/sluzbeni/2026_11_132_1583.html)
- [NN 132/2026, br. 1584, objava cjenika](https://narodne-novine.nn.hr/clanci/sluzbeni/2026_11_132_1584.html)
- [EAN za YakimaSport Rebounder 100011](https://yakimasport.com/pl/rebounder-trenazer-pilkarski/rebounder-rama-z-siatk%C4%85.html)
- [Your Europe – zakonsko jamstvo](https://europa.eu/youreurope/jamstva_hr)

Propisi za ovaj webshop provjereni su 18. studenoga 2026.
