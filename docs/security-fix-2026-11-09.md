# Sigurnosni popravak — 9. studenoga 2026.

## Opseg i granice nalaza

Lokalnim pregledom potvrđene su slabosti koje omogućuju promjenu korisničke uloge kroz spremanje profila, izmjenu tuđeg računa, neovlašten pristup administrativnim API zahtjevima te označavanje online narudžbe plaćenom bez valjano provjerene potvrde pružatelja plaćanja. Javni odgovor `/api/v2/settings/get` sadržavao je i privatne postavke plaćanja.

Pregledom produkcije bez promjena potvrđen je Git repozitorij `agmedia/top-tim`, grana `master`, početni HEAD `fc3502c0`, u `/home/toptimco/public_html`, uz CLI PHP 8.2.35. Javni odgovor stvarno je sadržavao neprazne myPOS konfiguracijske pakete za testno i produkcijsko okruženje; njihove vrijednosti nisu ispisivane. myPOS i virman bili su aktivni, a WSPay nije bio označen aktivnim. Popravak ne uključuje aktiviranje WSPaya.

Nalazi ne utvrđuju tko je ranije iskorištavao produkciju, koje su narudžbe eventualno pogođene ili je li netko ranije preuzeo privatne postavke. Za takav zaključak potrebni su produkcijski zapisi i usklađivanje narudžbi s evidencijom pružatelja plaćanja. Automatski testovi nisu provedeni nad produkcijskom bazom niti su naplaćivali stvarne kartice.

Prema korisnikovoj uputi ovaj popravak čuva postojeće korisničke lozinke, račune, ključeve i vjerodajnice pružatelja plaćanja. Ne resetira lozinke, ne rotira ključeve i ne prepravlja povijesne narudžbe. Zatvaranje javnog odgovora samo po sebi ne poništava moguće ranije preuzimanje podataka.

## Što se mijenja

- Profil se može spremiti samo za prijavljenog vlasnika računa. Ažuriraju se izričito dopušteni adresni i kontaktni podaci; dodatna polja za ulogu, lozinku, e-mail, grupu, status i bodove ne mogu pokrenuti administrativni uređivač.
- Administracija i administrativni API zahtjevi zahtijevaju stvarnu ulogu `admin`, `superadmin` ili `editor`. Računi bez uloge ili s proizvoljnom drugom ulogom nemaju pristup. Košarica, izbor opcija proizvoda i javno pretraživanje ostaju dostupni kupcima.
- Upravljanje korisnicima dostupno je administratorima i superadministratorima. Administrator ne može dodijeliti ulogu `superadmin` niti uređivati postojeći superadministratorski račun. Livewire ponavlja provjeru administratorskog pristupa i nakon što je stranica već otvorena.
- Javni dohvat postavki vraća samo dopuštena polja `currency.list` potrebna za prikaz cijena. Ne vraća postavke plaćanja, dostave ni ostale privatne postavke. Novi ključ predmemorije sprječava posluživanje starog odgovora s tajnama.
- myPOS prihvaća plaćanje samo iz provjerene potpisane serverske obavijesti `IPCPurchaseNotify`, uz podudaranje trgovca, narudžbe, iznosa i EUR valute. Povrat kupca ne provodi naplatu.
- WSPay provjerava SHA-512 potpis povrata i povezuje ga s jedinstvenim prethodno spremljenim pokušajem plaćanja i njegovim iznosom. Obrazac šalje `Version=2.0` i traži povrat putem GET zahtjeva.
- Prikaz stranice uspjeha ne mijenja status, zalihe ni evidenciju transakcija. Dovršavanje koristi zaključavanje narudžbe i zapis dovršetka kako ponovljena potvrda ne bi ponovno skidala zalihu ili slala potvrde.
- Virman, pouzeće i preuzimanje dovršavaju narudžbu kao novu, neplaćenu narudžbu. Slanje tih obrazaca i pokretanje myPOS plaćanja koriste POST sa zaštitom CSRF i narudžbom iz vlastite sesije. Završena narudžba ne može se prepisati naknadnim uređivanjem košarice.

## Provjere

Regresijski testovi koriste odvojenu SQLite bazu u memoriji i testne ključeve. Pokrivaju promjenu uloge kroz profil, izmjenu tuđeg računa, pristup bez ovlasti, Livewire nakon oduzimanja uloge, javno izlaganje postavki, lažne i nepodudarne potvrde plaćanja, ponavljanje potvrde, stanje zaliha te neplaćene offline narudžbe.

```sh
DB_CONNECTION=sqlite DB_DATABASE=:memory: DATABASE_URL= DB_URL= \
  /path/to/php82 vendor/bin/phpunit \
  --filter 'CheckoutPaymentSecurityTest|ProfileSecurityTest|PublicSettingsSecurityTest|LivewireAdminAccessTest' \
  tests/Feature
git diff --check
```

Zabilježeni lokalni rezultat: **42 sigurnosna testa, 241 provjera, sve uspješno** na PHP-u 8.2. Zajedno s postojećim provjerama cijena, SEO-a, preusmjeravanja, raskida ugovora i analitike prolaze **142 testa s 909 provjera**. Provjera sintakse svih 22 izmijenjenih i novih PHP datoteka također je uspješna. Opći stari testovi koji pokreću sve migracije imaju postojeću prepreku: dva migracijska zapisa definiraju klasu `CreateAttributesTable`; to nije mijenjano ovim popravkom. Izolirani sigurnosni testovi ne ovise o tom problemu.

## Pregled prije objave

1. Pregledati konačni diff, ponoviti provjere nad točnim commitom i zabilježiti njegov puni identifikator. U sigurnosni commit uključiti samo pripadajući kod, testove i ovu dokumentaciju. Postojeće korisnikove staged slike i ostale nepovezane datoteke ostaviti nepromijenjene.
2. Ponovno potvrditi produkcijski checkout i PHP te da su jedine postojeće izmjene praćenih datoteka `config/mail.php` i `public/.htaccess`. Obje se moraju očuvati, uz postojeće medije i ostale podatke. Ako se stanje razlikuje od pregledanog ili Git odbije ažuriranje, stati i utvrditi uzrok; ne improvizirati prijenos datoteka.
3. Prije eventualnog budućeg uključivanja WSPaya provjeriti povratne URL-ove i podršku obrascu verzije 2.0. `/narudzba` nije izuzet iz CSRF zaštite: WSPay povrat u ovom rješenju mora koristiti GET. Gateway POST na tu adresu bez CSRF tokena dobio bi 419. Ne dodavati opće izuzeće za `/narudzba`. Potpis WSPaya oslanja se na tajnost zajedničkog ključa; sam potpis ne štiti od osobe koja taj ključ već zna. Prije uključivanja takav scenarij zahtijeva potvrđenu serversku provjeru statusa kod pružatelja ili zamjenu kompromitiranog ključa. U ovom izdanju WSPay ostaje isključen.
4. Provjeriti da myPOS koristi postojeći `/mypos/notify` za potpisani `IPCPurchaseNotify`. Ne mijenjati postojeće ključeve ili lozinke. Pohraniti referencu prethodnog produkcijskog commita. Ovo izdanje ne provodi migracije niti izmjene povijesnih zapisa u bazi.
5. Računati da stari, već otvoreni WSPay obrasci bez novog zapisa pokušaja neće zadovoljiti novu provjeru; kupac treba ponovno otvoriti naplatu. Već nastale povijesne nelogičnosti u statusima rješavati zasebno usporedbom s pružateljem plaćanja.

## Objava isključivo kroz Git

Ovo je predviđeni postupak, a ne potvrda da je produkcija ažurirana. Izmjene najprije moraju biti pregledane, testirane, commitane i pushane. Aktivacija smije koristiti samo utvrđeni projektni postupak koji se oslanja na Git i točan odobreni commit.

Sigurnosni commit pripremiti i provjeriti u čistom izvornom checkoutu, ne uključujući korisnikove staged slike. Nakon pregleda i pusha na `origin/master`, u produkcijskom checkoutu `/home/toptimco/public_html` dohvatiti granu i provesti isključivo fast-forward na puni identifikator točno tog provjerenog commita:

```sh
git fetch origin master
git merge --ff-only "$release_commit"
git rev-parse HEAD
git diff --name-only
```

Prije pokretanja `$release_commit` mora biti postavljen na puni potvrđeni identifikator, a trenutna grana mora biti `master`. Nakon ažuriranja HEAD mora točno odgovarati tom identifikatoru, a razlika praćenih datoteka ostati ograničena na nepromijenjene postojeće `config/mail.php` i `public/.htaccess`; prije i poslije usporediti njihove lokalne razlike. Ne upotrebljavati `reset --hard`, `clean`, prisilni checkout ni automatsko spremanje/brisanje lokalnih izmjena. Ako merge nije moguć bez zahvaćanja tih datoteka, prekinuti objavu. Ne kopirati kod iz radne kopije na server. Ovaj popravak ne uvodi migracije baze niti zahtijeva seedanje.

Nakon aktivacije pokrenuti čišćenje Laravel predmemorije iz korijena tog izdanja, binarnom datotekom PHP-a koja odgovara produkcijskom web procesu:

```sh
"$production_php" artisan optimize:clear
```

Ta naredba čisti i aplikacijsku predmemoriju. Ponovno graditi konfiguracijsku predmemoriju ili ponovno pokretati dugotrajne radnike/OPcache samo ako to predviđa utvrđeni postupak projekta. Ne uvoditi nove korake kao što su `composer update`, migracije ili promjena vjerodajnica.

## Potvrda nakon objave

- Zabilježiti produkcijski commit i uspješan završetak objave/čišćenja predmemorije.
- Provjeriti prijavu kupca, spremanje vlastitog profila, odbijanje tuđeg profila i odbijanje administrativnih API zahtjeva kupcu i gostu. Potvrditi da ovlašteni djelatnici mogu obavljati svoj posao.
- Provjeriti da javni `/api/v2/settings/get` sadrži samo `currency.list` i da prikaz cijena i košarica rade.
- U odobrenom testnom okruženju pružatelja plaćanja provjeriti uspješnu i odbijenu naplatu, povrat kupca te ponovljenu serversku obavijest. Potvrditi jednu promjenu zalihe i jedan dovršetak narudžbe. Produkcijske narudžbe ne označavati plaćenima radi provjere.
- Potvrditi da virman/pouzeće/preuzimanje ostaju neplaćeni. Pratiti pogreške povratnih zahtjeva i uskladiti stvarne uplate s narudžbama prije zaključka da je problem u produkciji riješen.

Povrat na staro izdanje nije automatski korak jer bi ponovno otvorio zatvorene propuste. Eventualni povrat mora imati izričitu korisnikovu uputu za točan povrat i provesti se istim Git postupkom.

## Protokol pružatelja plaćanja

- [myPOS Purchase Notify](https://developers.mypos.com/apis/checkout-api/purchase-notify): potvrda plaćanja dolazi preko potpisane serverske obavijesti.
- [myPOS Purchase Rollback](https://developers.mypos.com/apis/checkout-api/purchase-rollback): provjerena obavijest o povratu mora ukloniti status plaćanja; kasna stara potvrda ne smije ponovno platiti poništenu narudžbu.
- [WSPayForm dokumentacija](https://docs.wspay.info/wspayform): obrazac verzije 2.0 i provjera povratnog potpisa.
