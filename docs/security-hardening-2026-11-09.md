# Dodatna sigurnosna zaštita — 9. studenoga 2026.

## Status i odnos prema prvom izdanju

Ovaj dokument opisuje dodatno izdanje pripremljeno nakon već objavljenog prvog sigurnosnog popravka, čiji je produkcijski commit `08df89703a82ce3512ae983a4f07adaec606e572`. Prvo izdanje, opisano u [security-fix-2026-11-09.md](security-fix-2026-11-09.md), zatvorilo je promjenu uloge kroz profil, nedopušten pristup administraciji, javno izlaganje privatnih postavki te neprovjerene potvrde plaćanja.

Ovdje opisane promjene još se moraju objaviti kroz pregledani Git postupak. Ovaj dokument i lokalni rezultati testova nisu potvrda da je novo izdanje aktivno na produkciji. Ne dokazuju ni je li neki propust ranije iskorišten; za to trebaju zasebna analiza zapisa i usklađivanje stvarnih uplata.

## Dodatno zatvoreni propusti

- **Recenzije:** javna prijava prihvaća samo dopuštena polja, postojeći aktivan proizvod, valjanu ocjenu i ograničene tekstualne vrijednosti. Kupac ne određuje status objave, istaknutost ni identitet drugog korisnika. Administrativni prikaz poruke sigurno ispisuje spremljeni tekst pa se sadržaj recenzije ne može izvršiti kao skripta. Slanje je ograničeno po učestalosti, a ponovljena recenzija istog prijavljenog korisnika za isti proizvod ne dodjeljuje ponovno bodove.
- **Obrada slika:** javni cache i thumbnail zahtjevi više ne dohvaćaju proizvoljne mrežne adrese. Izvor se razrješava isključivo u postojeću lokalnu raster sliku unutar `public/media`; podržani relativni i konfigurirani vlastiti URL-ovi mapiraju se na lokalnu datoteku. Provjeravaju se putanja, stvarna lokacija nakon razrješavanja simboličkih poveznica, format, veličina datoteke, broj piksela i izlazne dimenzije. Odgovor se ponovno kodira kao PNG i predmemorira Laravelovom predmemorijom. Zahtjevi imaju ograničenje učestalosti.
- **Upload slika bloga i recepata:** stvarni sadržaj mora biti podržana raster slika; PHP, SVG i ostale datoteke se odbijaju. Slika se ponovno kodira, dobiva nasumično ime s fiksnom `.png` ekstenzijom, a klijentska putanja ne određuje odredište spremanja. Novi `public/media/.htaccess` dodatno zabranjuje skripte, skrivene datoteke i popis direktorija u medijima.
- **Prijava i oporavak računa:** neaktivni i uklonjeni računi ne mogu otvoriti novu prijavu niti nastaviti kroz zaštićene zahtjeve postojećom sesijom ili API tokenom. Prijava iz naplate koristi uobičajeni Fortify postupak, uključujući dvofaktorsku provjeru kada je uključena. Resetiranje lozinke koristi hashirane, vremenski ograničene i jednokratne Laravel tokene; raniji sirovi tokeni se ne prihvaćaju. Zahtjevi imaju ograničenje učestalosti i jednak javni odgovor za postojeću i nepostojeću adresu. Uklonjen je prikaz cijele sesije na stranici prijave.
- **Narudžbe kupca:** prikazuju se samo narudžbe povezane s njegovim stvarnim `user_id`. Nepotvrđena e-mail adresa više ne prisvaja tuđe ili gostujuće narudžbe. Izdanje ne prepisuje povijesne veze narudžbi; gostujuće narudžbe ne povezuje naknadno samo podudaranjem adrese.
- **Dostava i način plaćanja:** odabir se ponovno provjerava na serveru prema državi, geografskoj zoni, aktivnim metodama, dostavi i sadržaju košarice. Cijena i raspoloživost metode proizlaze iz konfiguracije na serveru. Popravljen je i slučaj prvog elementa države u geografskoj zoni. Promjena ne zamjenjuje postojeće vjerodajnice pružatelja plaćanja.

## Sigurno upravljanje korisnicima

Produkcijska provjera otkrila je da postojeći `CACHE_DRIVER=array` ne čuva brojače između HTTP zahtjeva, pa prethodna ograničenja učestalosti nisu bila djelotvorna. Nova postavka `cache.limiter` u produkciji izričito koristi trajni datotečni spremnik, dok zadana predmemorija aplikacije ostaje nepromijenjena. Lokalno i u testovima limiter koristi zadani spremnik radi izolacije. Regresijska provjera potvrđuje odabir konfiguracije i dijeljenje istog ograničenja između dvaju odvojenih limiter spremnika. Produkcijski direktorij `storage/framework/cache/data` mora biti zapisiv PHP procesu; `.env` se ne mijenja.

Popis, uređivanje, izvoz i uklanjanje korisnika zahtijevaju aktivnog administratora ili superadministratora. Popis omogućuje pretraživanje, filtriranje po stvarnim autorizacijskim ulogama i dopušteno sortiranje; isti filtri i poredak vrijede za Excel izvoz. Ulazni podaci ne postaju proizvoljni SQL stupci ili izrazi. Izvoz zapisuje korisnički tekst kao tekstualne ćelije pa se ne tumači kao Excel formula.

Uklanjanje zahtijeva CSRF-zaštićen zahtjev i potvrdu u sučelju. Administrator ne može ukloniti sebe niti upravljati superadministratorom; superadministrator može upravljati drugim superadministratorom. Ovlasti i stanje računa ponovno se provjeravaju u transakciji sa zaključavanjem redaka. Uklanjanje je logičko (`deleted_at`): onemogućuje pristup, opoziva tokene i briše pripadajuće baze sesija kada se koristi taj način spremanja, uz zapis identifikatora djelatnika i ciljnog računa. Narudžbe i njihova povijest ostaju sačuvane. Izdanje samo po sebi ne uklanja nijednog stvarnog korisnika.

## Ovisnosti i kompatibilnost

Izdanje podiže aplikaciju na Laravel 12, Livewire 3, Jetstream 5 i Sanctum 4, uz PHP **8.2 ili noviji**. PhpSpreadsheet 5 zamjenjuje staru verziju i stari Excel omotač; frontend koristi Vue 3, Vuex 4 i Vue Router 4 te obnovljen postupak gradnje. Lock datoteke određuju točne pregledane verzije. Produkcija instalira ovisnosti s `composer install`, bez `composer update`.

Prilagođeni su Livewire događaji i imenovani parametri, reaktivna polja, objavljene Jetstream komponente i vezanje modalnih prozora. Frontend prilagodbe obuhvaćaju Vue slotove, vezanje ocjene, jQuery prije ovisnih skripti i URL-ove novih runtime datoteka. Uklonjena je stara ovisnost `intervention/imagecache`; sigurna obrada slika koristi postojeći Intervention Image 2 API i Laravel cache. Provjere importa koriste odvojene privremene diskove i valjane testne slike te ne pišu u korisnikove medije.

Korisnik je izričito odobrio uključivanje **nužnih izgrađenih JS i Livewire runtime datoteka za ovo izdanje** u Git. To je konkretno odobrenje za datoteke koje trebaju raditi s promijenjenim izvornim kodom, a ne opće odobrenje za nepovezane generirane datoteke ili postojeće korisnikove slike. Runtime datoteke grade se i pregledavaju prije commita; serverska skripta ih preuzima kao dio točno određenog Git commita i ne prepisuje ih ručnim prijenosom ili objavljivanjem paketa na produkciji.

## Provjere prije objave

Zabilježeni lokalni rezultat cijelog PHP skupa jest **240 testova, 1192 provjere, 0 neuspjeha i 6 preskočenih testova** na PHP-u 8.2. Preskočeni testovi ostaju izričito preskočeni; nisu predstavljeni kao uspješno izvršeni. Dvije JavaScript provjere izvršavaju stvarne izgrađene bundleove i potvrđuju košaricu, dodavanje proizvoda, stanje Vuex, prikaz valuta, paginaciju te slanje odabrane ocjene. Produkcijska gradnja JS datoteka prolazi.

Composer i npm audit za zaključane ovisnosti prijavljuju **0 poznatih sigurnosnih upozorenja** u trenutku provjere. Taj rezultat odnosi se na obuhvaćene ovisnosti i poznate baze upozorenja; nije jamstvo da cijela aplikacija nema drugih propusta. Sigurnosni testovi pokrivaju odbijanje SSRF izvora i putanja izvan medija, upload skripti i SVG-a, ponovno kodiranje slika, recenzije i moderaciju, resetiranje računa, neaktivne račune, narudžbe, naplatu te administratorske ovlasti i Excel izvoz. Ne koriste produkcijsku bazu, stvarne kartice ili stvarne korisničke podatke.

Prije commita pregledati konačnu razliku i izdvojiti samo pripadajuće promjene. Postojeće staged slike, nepovezani mediji i lokalni arhivi ostaju izvan sigurnosnog commita. Rezultate i puni identifikator konačnog commita provjeriti prije pusha i aktivacije.

## Git postupak objave

Postupak definira verzionirana skripta [scripts/deploy-security-hardening.sh](../scripts/deploy-security-hardening.sh) s fazama `stage` i `activate`. Prima puni identifikator pregledanog commita. Obje faze zahtijevaju produkcijsku granu `master`, potvrđeni privatni SSH origin, zaseban read-only deploy ključ i strogu provjeru GitHubova host ključa. Dohvaćeni `origin/master` mora točno odgovarati tom commitu i potjecati od prvog objavljenog izdanja. Skripta odbija neočekivane lokalne promjene, staged promjene, promjene zaštićene konfiguracije i zahvate u korisnikove medije osim nove zaštitne `.htaccess` datoteke.

1. **Stage:** dohvaća potvrđeni Git commit, stvara odvojeni worktree izvan web checkouta i u njemu instalira produkcijske ovisnosti iz `composer.lock`. Koristi Composer 2.11.1 s provjerenim službenim SHA-256 sažetkom, isključuje scripts i provjerava PHP platformu. Produkcijska `.env` ne kopira se u worktree; aktivni checkout i njegov `vendor` ostaju nepromijenjeni. Uspješna faza zapisuje identitet baze izdanja, zaštićene konfiguracije i lock datoteke za kasniju provjeru.
2. **Activate:** ponovno provjerava stanje, uključuje unaprijed renderiranu stranicu održavanja i radi isključivo Git fast-forward na isti commit. Instalira produkcijske ovisnosti iz lock datoteke, provodi standardno otkrivanje Laravel paketa, točno dvije navedene migracije, čisti i obnavlja konfiguracijsku i view predmemoriju te signalizira ponovno pokretanje queue radnika. Provjerava da se zaštićena konfiguracija i praćeni kod nisu neočekivano promijenili. Tek nakon uspjeha isključuje održavanje.

Skriptu treba pokrenuti iz potvrđenog produkcijskog checkouta, verzijom PHP-a koja odgovara web procesu. Bootstrap skripte također dolazi iz dohvaćenog Git commita, primjerice uz već postavljen puni identifikator `$release`:

```sh
git show "$release:scripts/deploy-security-hardening.sh" | bash -s -- stage "$release"
git show "$release:scripts/deploy-security-hardening.sh" | bash -s -- activate "$release"
```

Faza `activate` smije slijediti tek nakon uspješnog `stage` i pregleda rezultata. Ako aktivacija stane nakon uključivanja održavanja, skripta ostavlja održavanje uključeno i ne pokušava automatski povrat. Najprije se utvrđuje uzrok i popravlja naprijed; povrat na ranjivo izdanje nije automatski oporavak. Svaki točno određeni povrat zahtijeva izričitu korisnikovu uputu i isti Git postupak. Nema `reset --hard`, `clean`, prisilnog checkouta, kopiranja koda na server ni ručnog uređivanja izvornog koda u cPanelu.

## Dvije aditivne migracije i očuvanje podataka

Aktivacija namjerno izvršava samo ove nove migracije:

| Migracija | Promjena | Učinak na postojeće podatke |
| --- | --- | --- |
| `2026_11_09_150100_add_expires_at_to_personal_access_tokens.php` | Dodaje nullable `expires_at` potreban Sanctumu 4 ako stupac nedostaje. | Ne prepisuje postojeće tokene. |
| `2026_11_09_160000_add_deleted_at_to_users_table.php` | Dodaje nullable `users.deleted_at` za logičko uklanjanje. | Ne uklanja niti deaktivira postojeće račune. |

Ne pokreću se sve povijesne migracije, seedanje, osvježavanje baze ili masovne promjene korisnika i narudžbi. Postojeće lozinke, `APP_KEY`, ključevi i podaci servisa za naplatu ostaju isti. Resetiranje lozinke mijenja lozinku samo kada korisnik kasnije sam dovrši valjani zahtjev za resetiranje. Produkcijske `.env`, `config/mail.php` i korijenski `public/.htaccess`, uključujući njihove postojeće lokalne izmjene, zaštićeni su provjerom sadržaja prije i poslije. Novi zaštitni `public/media/.htaccess` ne zamjenjuje korijensku konfiguraciju. Ako ta datoteka već postoji kao nepraćena produkcijska datoteka, aktivacija staje radi pregleda umjesto prepisivanja.

## Potvrda nakon aktivacije

Potvrditi produkcijski HEAD i završetak skripte, očuvane sažetke zaštićene konfiguracije, uspješne dvije migracije te PHP verziju web procesa. Zatim provjeriti početnu stranicu, običnu sliku iz medija, košaricu, prijavu, profil i administratorski popis s pretraživanjem, ulogama i izvozom. Provjeriti odbijanje skripti u medijima, pristupa administraciji bez ovlasti i nedopuštenih image-cache izvora. Ne uklanjati stvarnog korisnika, ne mijenjati njegovu lozinku i ne slati stvarnu naplatu radi provjere.

Nova izdanja, migracije i runtime datoteke smatraju se objavljenima tek kada su potvrđeni točan produkcijski commit i stvarno ponašanje nakon aktivacije. Do tada se stanje izvještava kao lokalno pripremljeno i testirano.
