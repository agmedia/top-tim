# Uređivanje najniže cijene artikla — 23. studenoga 2026.

Na popisu artikala ispod prodajne cijene prikazuje se najniža zabilježena cijena. Poveznica za uređivanje vodi na zaseban odjeljak na uređivanju postojećeg artikla. Aktivni administratori i superadministratori mogu spremiti ručni ispravak uz razlog ili vratiti automatski izračun. Urednici imaju prikaz bez mogućnosti izmjene.

Ručni ispravak vrijedi 30 dana od spremanja. Dok vrijedi, prikazuje se točno upisani iznos, neovisno o automatskom izračunu ili naknadnom bilježenju javnih cijena. Nakon isteka ponovno se koristi automatski minimum; stariji ručni ispravak ne postaje ponovno aktivan. Izvorna evidencija cijena ostaje sačuvana, a razlog, autor i vrijeme svakog ispravka ostaju u povijesti. Razlog i autor ne šalju se u javni katalog.

Spremanje koristi zasebni CSRF zaštićen PATCH zahtjev, validaciju iznosa i razloga te ponovnu provjeru aktivnog administratorskog računa unutar transakcije. Promjena ovog polja ne mijenja redovnu ni akcijsku cijenu, bazne cijene, XML cjenike, narudžbe ili podatke za naplatu. Vraćanje na automatski izračun dodaje zapis u povijest, bez brisanja ranijih zapisa.

Objava ide kroz [Git skriptu](../scripts/deploy-admin-lowest-price-20261123.sh), uz pregled, testove, commit i push. Očekivana baza je `a57675a848e2d04d8606bbd4fc57e08464abbd32`. Skripta zahtijeva točan `origin/master`, radi fast-forward i primjenjuje isključivo novu migraciju `2026_11_23_180000_create_product_lowest_price_corrections.php`. Migracija dodaje odvojenu tablicu ispravaka bez prepisivanja povijesnih cijena. `.env`, postojeći `config/mail.php` i `public/.htaccess` provjeravaju se prije i poslije objave.

```sh
git fetch origin master
git show "$release:scripts/deploy-admin-lowest-price-20261123.sh" | bash -s -- "$release"
```

Nakon objave provjeriti produkcijski commit, migraciju, zaštićene rute i prikaz novog odjeljka u adminu. Spremanje cijena, ovlasti, povratak na automatski izračun, vremensko važenje i očuvanje izvornih zapisa testiraju se na odvojenim sintetičkim podacima. Postojeće produkcijske cijene ne mijenjati radi provjere sučelja.
