# Slike kataloga — 23. studenoga 2026.

Obrazac artikla sada prihvaća podržane slike do **5 MiB**, uz provjeru formata i dimenzija na serveru. U kategorijama izvorna slika i izvedeni WebP/thumbnail koriste isto osnovno ime. Ispravljen je pogrešan naziv diska pri zamjeni slike proizvoda; neuspješan upload čuva prethodnu sliku. Javni media diskovi stvaraju direktorije s dozvolama `0755`, a datoteke s `0644`.

Postojeći pogođeni direktoriji kategorija `140`, `141`, `142` i proizvoda `3623` pregledani su kao postojeći direktoriji s dozvolama `0700`; pripadajuće slike već imaju `0644`. Novi `media:repair-permissions` zadano samo prikazuje plan. Uz `--apply` mijenja samo dozvole navedenih direktorija i njihovih izravnih slikovnih datoteka: ne slijedi poveznice, ne obilazi poddirektorije, ne prepisuje sadržaj i ne mijenja bazu. Nema masovnog popravljanja svih medija.

Git postupak definira [deploy-catalog-images-20261123.sh](../scripts/deploy-catalog-images-20261123.sh). Prima puni pregledani commit, zahtijeva produkcijski `master`, privatni SSH origin, postojeći read-only deploy ključ i strogu provjeru hosta. Produkcijska baza mora biti `3926def12dfd91003e86c610257d64cd2bdc9fad`, a `origin/master` točno traženo izdanje. Skripta dopušta samo navedene datoteke ovog popravka; odbija staged promjene i sve postojeće tracked izmjene osim `config/mail.php` i `public/.htaccess`.

Prije aktivacije bilježi sažetke `.env`, `config/mail.php` i korijenskog `public/.htaccess` izvan web checkouta. Zatim izvršava Git fast-forward, obnavlja konfiguracijsku i view predmemoriju te za navedene ID-jeve pokreće probni pregled dozvola i primjenu. Nema instaliranja ovisnosti, migracija, kopiranja koda, nove stranice održavanja ni automatskog povrata. Ponovni poziv istog izdanja nakon djelomičnog prekida zahtijeva isti izvorni zapis zaštićene konfiguracije i ponovno provodi iste provjere.

Nakon što je pregledano izdanje commitano i pushano, dohvatiti ga kroz postojeći Git postupak. Iz produkcijskog checkouta, uz puni identifikator u varijabli `release`, pokrenuti skriptu iz tog Git objekta:

```sh
git fetch origin master
git show "$release:scripts/deploy-catalog-images-20261123.sh" | bash -s -- "$release"
```

Potvrditi završnu poruku, produkcijski HEAD, očuvane sažetke konfiguracije i dostupnost pogođenih slika preko javnih URL-ova. Stanje je lokalno pripremljeno dok točno izdanje i ponašanje nakon aktivacije nisu zasebno potvrđeni. Postojeće lozinke i podaci za naplatu ostaju isti.
