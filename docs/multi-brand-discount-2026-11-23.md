# Više brendova u količinskom popustu — 23. studenoga 2026.

Polje **Brendovi** omogućuje odabir više brendova u jednom količinskom popustu. Sve razine uređuju se i spremaju zajedno za odabrani skup, uz postojeće opsege Svima, korisnik ili grupa. Količine se i dalje zbrajaju zasebno za svaki stvarni brend, kroz njegove artikle i varijante; različiti brendovi ne zbrajaju se u isti prag. Primjenjuje se najpovoljnija cijena, bez zbrajanja popusta.

Odabir se čuva kao sortirani skup pozitivnih ID-jeva i njegov ključ izveden na serveru. Stari pojedinačni brend ostaje u istom odabiru, s istim iznosima, pragovima, aktivnošću i primateljem. Kompatibilno polje prvog brenda ostaje sačuvano; zajednički izračun provjerava svaki odabrani brend, uključujući neprvi. Popis prikazuje sve odabrane brendove. Skupovi koji dijele jedan brend ostaju odvojeni pri uređivanju i brisanju svih razina.

Validacija zahtijeva 1–100 različitih postojećih brendova. Stariji već otvoreni obrasci s jednim `brand_id` i dalje se prihvaćaju. Privatna pravila ostaju odvojena od javnog opsega Svima. Javni pojedinačni izračun za evidenciju cijena i buduće XML cjenike koristi isti odabir, uz postojeće isključivanje privatnih pravila i viših količinskih pragova.

Objava se provodi kroz [Git skriptu](../scripts/deploy-multi-brand-discount-20261123.sh), nakon pregleda, testova, commita i pusha. Baza je `3f11c3ae5527776b0c062a8ebcb524d99eccc7fb`. Skripta provjerava točan privatni `origin/master` i zaštićene postavke, nakratko uključuje vlastito održavanje, radi fast-forward i primjenjuje samo `2026_11_23_200000_add_brand_sets_to_customer_discounts.php`. Migracija dodaje i popunjava samo dva nova polja odabira; ponavljanje ne prepisuje dovršene skupove. Rute, nova polja i popunjenost podataka provjeravaju se prije vraćanja stranice u rad. `.env`, postojeći lokalni `config/mail.php` i `public/.htaccess` ostaju očuvani.

```sh
git fetch origin master
git show "$release:scripts/deploy-multi-brand-discount-20261123.sh" | bash -s -- "$release"
```

Ako objava stane tijekom održavanja, pregledati problem i nastaviti unaprijed istu provjerenu verziju istom skriptom; nema automatskog povrata. Povrat migracije odbija ukloniti spremljeni odabir više brendova. Ne pokreće se izdavanje cjenika niti bilježenje cijena tijekom objave. Produkcijska pravila ne mijenjati radi provjere obrasca; spremanje i obračun provjeriti na sintetičkim podacima.
