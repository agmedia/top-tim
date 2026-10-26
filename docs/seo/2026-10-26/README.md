# Top Tim — provjera 404 URL-ova i tehnički SEO

Datum provjere: 26. 10. 2026. Izvor: Google Search Console, entitet `https://www.top-tim.com/`, izvještaj „Nije pronađeno (404)”, posljednje ažuriranje 23. 10. 2026.

## Rezultat provjere svih 83 URL-a

| Odluka | Broj | Obrazloženje |
| --- | ---: | --- |
| Pripremljen 301 na provjereno aktivno odredište | 29 | 21 brend, 3 kategorije starog webshopa i 5 preimenovanih proizvoda. |
| Zadržati ispravan 404 | 53 | 52 proizvoda bez potvrđenog identičnog aktivnog odredišta i testna adresa `/test`. |
| Zadržati 404 do potvrde identiteta artikla | 1 | Stara „Trenirka komplet Academy” ima dvije moguće Academy IV zamjene s različitim šiframa; nema dovoljno podataka za automatski 301. |

Svih 83 izvezenih adresa provjereno je HTTP zahtjevom do konačnog odgovora: sve vraćaju 404 prije objave ovih promjena. Jedan URL u izvještaju sadrži razmak i novi red u parametrima; ponovljena provjera s URL kodiranjem također vraća 404. Proizvodi su uspoređeni s aktualnim sitemapom od 2.117 stavki i javnom pretragom kataloga. Sličan naziv ili novija generacija proizvoda nisu korišteni kao dokaz identiteta.

- [Odluka i odredište za svaki od 83 URL-a](gsc-404-audit.csv)
- [Izvorni popis iz Search Consolea](gsc-404-source.csv)
- [HTTP, canonical i robots provjera kategorija, brendova i stranica](live-category-brand-page-audit.csv)
- [Dodatna provjera pronađenih proizvoda, naziva i šifri](verified-product-targets.csv)

CSV datoteke opisuju zatečeno stanje produkcije prije objave. „301 pripremljen” znači da je preusmjeravanje implementirano i testirano u kodu; ne predstavlja potvrdu da je već aktivno na produkciji.

## Implementirani ispravci

- `/macron` → `/brand/macron` i `/zeus` → `/brand/zeus`. Postojeće aktivne stranice brendova ostaju odredišta; nije potrebno stvarati njihove duplikate kao kategorije.
- Ukupno 38 točnih starih putanja u `config/seo_redirects.php`: 29 iz GSC izvještaja, četiri dodatna brenda pronađena u starim canonical oznakama i pet inačica starih proizvoda bez prefiksa `/hr/`. Odredišta su provjerena kao izravan 200 s odgovarajućim sadržajem. GET i HEAD vraćaju 301, parametri se čuvaju, a popis nema petlje ni međusobne lance preusmjeravanja.
- Četiri preimenovana proizvoda potvrđena su šiframa `LSM1062`, `LSM1184`, `LSK213` i `LSG032`. „Dres komplet Sticker” preusmjeren je na isti naziv unutar iste kategorije rasprodaje, čiji novi slug ima vremenski sufiks.
- Canonical oznake svih brendova upućuju na `/brand/{slug}`. Prije ispravka svih 26 pojedinačnih brendova navodilo je canonical na nepostojeću adresu u korijenu domene.
- Canonical podkategorije zadržava roditeljsku kategoriju; prije ispravka 66 od 78 kategorijskih URL-ova imalo je drukčiju canonical adresu. Grupa se uzima iz kategorije kako proizvoljan alias grupe ne bi dobio vlastiti canonical. Paginacija zadržava valjani `page`, a filtri ne stvaraju nove canonical adrese.
- Popravljene su canonical oznake naslovnice i blog članaka te dodane oznake indeksu brendova, informacijskim stranicama, kontaktu, FAQ-u i obrascu raskida ugovora.
- `robots.txt` sada navodi `https://www.top-tim.com/sitemap`; prije je navodio drugu domenu (`plavakrava.hr`).
- Sitemap generira aktualne putanje umjesto zastarjelih spremljenih URL-ova, izostavlja neobjavljene stavke i stavke bez potrebnog prijevoda/sluga ili valjane kategorije te uklanja ponovljene URL-ove brendova. Popravljen je operator upita za glavne kategorije. Nepoznata sitemap datoteka vraća 404; slikovni aliasi koriste slikovni XML format.
- Interne poveznice proizvoda, navigacije i karusela koriste ispravne adrese bez nepotrebnog `/hr/` preusmjeravanja za zadani jezik. Poveznice za prijavu otvaraju modal umjesto nepostojeće `/signin-tab` stranice. Navigacija izostavlja neobjavljene stranice i stranice bez prijevoda.
- Završna ruta za nepostojeće putanje vraća stvarni HTTP 404 umjesto 200 s izgledom stranice pogreške.

## Provjera

Prije objave potvrđen je HTTP 200 za svih 78 kategorija, 27 jedinstvenih URL-ova brendova i 10 stranica iz njihovih sitemapova. Na tim stranicama nisu pronađeni meta robots ili X-Robots-Tag koji zabranjuju indeksiranje. Macron, Zeus i sva odredišta novih preusmjeravanja zasebno su provjereni.

Automatizirana provjera nakon ispravaka: **96 testova, 647 tvrdnji, sve prolazi**. Obuhvaćeni su GET/HEAD odgovori svih 38 preusmjeravanja, parametri i završna kosa crta, zaštita od petlji i vanjskih odredišta, stvarni 404, sadržaj XML sitemapova, canonical kategorija i brendova, jezične inačice URL-ova proizvoda i renderirana kontaktna stranica s jednim canonicalom i izravnim poveznicama. Uključeni su postojeći testovi cijena i raskida ugovora. Testovi koriste izoliranu SQLite bazu i ne mijenjaju produkcijske podatke.

Ponovljiva provjera:

```sh
php -d error_reporting=22527 vendor/bin/phpunit --filter 'LegacyCatalogRedirectTest|SeoSitemapTest|ActionPriceTest|ContractTerminationTest'
```

## Preostalo i provjera nakon objave

1. **Objava na produkciju:** nakon preuzimanja commita obnoviti eventualni Laravel cache konfiguracije i ruta uobičajenim postupkom objave. Zatim ponoviti produkcijsku provjeru 301 → 200, canonical oznaka, robots.txt i XML sitemapova. Git push sam po sebi nije dokaz objave.
2. **Engleska naslovnica `/en/` vraća HTTP 500.** GSC bilježi taj jedan 5xx URL, zadnje pretražen 6. 10. 2026.; sadašnja provjera potvrđuje 500. Potrebna je zasebna provjera engleskih prijevoda/sadržaja i produkcijskog zapisa greške. Ovaj popravak ne isključuje engleski jezik niti preusmjerava cijelu jezičnu inačicu.
3. **Potvrditi stari artikl Academy:** moguća odredišta su `/kategorija-proizvoda/rasprodaja/trenirka-komplet-academy-iv` (`JOM20003`) i `/kategorija-proizvoda/trenirke/kompleti/joma-trenirka-komplet-academy-iv` (`JOMA101966`). Stara šifra nije dostupna u GSC izvozu. Do potvrde ostaje 404.
4. **Urednički provjeriti `/brand/test`.** Aktivna je testna stranica brenda. Stara `/test` adresa namjerno nije preusmjerena. Preporučeno je odlučiti treba li testni zapis deaktivirati u administraciji.
5. **GSC potvrda:** ne pokretati „Provjeri ispravak” prije stvarne objave i produkcijske provjere. Očekivani 404 odgovori uklonjenih proizvoda mogu ostati u izvještaju; nisu svi 83 URL-a kandidati za 301 niti se očekuje da svi budu indeksirani.
6. **Drugi GSC statusi:** 17 URL-ova s noindexom, 2 duplikata bez odabranog canonicala, 181 otkriven ali neindeksiran URL i 36 pretraženih ali neindeksiranih URL-ova zahtijevaju zasebnu analizu ako ostanu nakon ponovnog Googleova pretraživanja. Zabilježena su i 64 preusmjeravanja te 7 alternativnih canonical stranica; ti statusi sami po sebi ne označavaju pogrešku. Za te skupine ovdje nije napravljena pojedinačna procjena URL-ova.

Izvor: [Google Search Console — Top Tim](https://search.google.com/search-console?resource_id=https%3A%2F%2Fwww.top-tim.com%2F).
