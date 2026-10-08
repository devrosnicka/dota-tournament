# Implementační plán

> Navazuje na [SPEC.md](SPEC.md). Odkazy „§x.y“ míří do zadání. Sekce 0 má přednost před zadáním. Sekce 3 obsahuje rozhodnutí tam, kde zadání mlčí.

## 0. Změny oproti zadání (odsouhlaseno 2026-10-07)

### Přihlášení místo §2.2 (login odkazem a QR)
Zadání chtělo v DB jen hash tokenu a zároveň QR s odkazem u admina, což nejde dohromady. Nahrazuje ho jednodušší řešení:
- Po registraci (přezdívka + `REGISTRATION_CODE`) je hráč rovnou přihlášený. Session platí 30 dní.
- Na jiném zařízení zadá na `/login` **dočasný 4místný kód**. Kód vygeneruje admin u hráče, nebo hráč sám na zařízení, kde už přihlášený je („Přihlásit jiné zařízení“). Kód platí 15 minut, je jednorázový a mezi platnými kódy unikátní.
- V `players` jsou místo `login_token_hash` sloupce `login_code` a `login_code_expires_at`. Odpadá token, odkaz `/login/{token}` i QR kódy.
- Rate limit na `/login` a na admin login zůstává.

### Shody místo §1.9 a hranice kapitánů
- Rozstřel 1v1 se hraje **jen o postup do finále** (hranice 10./11. místa mezi aktivními hráči) **a o šampiona** (§1.11 beze změny).
- Všechny ostatní shody po bodech a rozdílu killů rozhoduje **nasazení** (lepší nasazení je výš). Týká se to i hranice kapitánů, pořadí 1./2. (volba výhody) a pořadí pro volbu rolí. Los v tabulce základní části odpadá.
- `tiebreak_orders.context` má jen hodnoty `qualification` a `champion`.

## 1. Stack a vývojové prostředí

| Oblast | Volba |
|---|---|
| Backend | Laravel 13, PHP 8.4, Inertia v3 |
| Frontend | React 19 + TypeScript, Tailwind 4, shadcn/ui, `@dnd-kit/sortable` (dotyk přes `TouchSensor`) |
| Testy | Pest 5 (unit + feature), Larastan, Pint, ESLint, `tsc` |
| DB | SQLite ve WAL režimu s `busy_timeout` (polling z mnoha mobilů najednou) |
| Session | driver `cookie` (šifrovaná a podepsaná), 30 dní, `secure` v produkci |
| Kontejner | FrankenPHP (jeden proces, uvnitř HTTP na portu 80) |

- Základem je oficiální Laravel React starter kit. Odstraní se z něj uživatelské účty (Fortify, passkeys, nastavení, tabulka `users`), zůstane layout a komponenty shadcn/ui.
- Lokálně není PHP ani Composer, proto vývoj poběží celý v Dockeru. `compose.yaml` s dev obrazem (PHP + Composer + Node), zdrojáky připojené jako volume, Vite dev server v kontejneru. Běžné příkazy zabalí skript `./dev` (`./dev up`, `./dev test`, `./dev check`), `make` na hostiteli není.
- Kód, identifikátory a commity budou anglicky. UI bude jen česky, bez i18n vrstvy, plus `lang/cs` pro validační hlášky. `APP_TIMEZONE=Europe/Prague`.

## 2. Architektura

```
app/
  Domain/                 čisté PHP: bez Eloquentu, bez HTTP, plně unit testované
    Seeding/SeedingService
    Schedule/FormatResolver, SitPlanner, ScheduleGenerator, TeamCost
    Standings/GroupStandings, OverallStandings
    Final/FinalDraft
    Support/Rng           obal nad Random\Randomizer + Mt19937 (deterministický ze seedu)
  Tournament/             aplikační vrstva: modely → DTO → doménová služba → uložení
    PhaseManager          stavový automat a kontroly přechodů (§2.3)
    ScheduleService       generování, prohození, publikace, regenerace po odstoupení
    ResultService, DraftService, TiebreakService, AuditLogger
  Http/
    Controllers/{Player,Admin,Tv}
    Middleware/           player, admin, tv-key, vyžadovaná fáze
resources/js/pages/{player,admin,tv}/…
```

- Doménové služby přijímají a vracejí jen readonly DTO. Náhoda jde vždy přes předaný `Rng`, takže testy jsou deterministické a rozpis jde reprodukovat ze seedu.
- Body ani pořadí se neukládají (§2.1). Tabulky se počítají při každém requestu, při 16 hráčích je to zanedbatelné.
- Zápisy do DB běží v transakcích a změny jdou do `audit_log`.

## 3. Upřesnění pravidel

### Nasazení (§1.2)
- Hodnocení se ukládá vždy jako celé pořadí najednou. Na začátku hráč vidí ostatní v náhodném pořadí a nic se neuloží, dokud neklikne „Uložit“.
- Hráč, kterého nikdo nehodnotil, dostane neutrální průměr N/2. Nastane to jen tehdy, když hodnocení odešle jediný hráč.
- **Los je deterministický.** Při uzavření hodnocení se vygeneruje a uloží náhodný `lottery_seed`. Pořadí losem se z něj odvozuje jako hash (seed + kontext + id hráče), takže se mezi načteními nemění. Los se používá už jen pro shody v nasazení a pro nasazení pavouka o šampiona.

### Plán sezení (§1.5)
- Pořadí priorit:
  1. rozdíl počtu sezení nejvýš 1 (vždy),
  2. nesedět dvě kola po sobě,
  3. mix polovin nasazení,
  4. los.
- **Doporučený algoritmus v zadání může porušit tvrdé pravidlo.** Zadání říká „z každé poloviny vyber kandidáty s nejnižším počtem sezení, když nestačí, doplň z druhé“. Když v horní polovině už všichni seděli a ve spodní ještě ne, algoritmus posadí podruhé někoho, kdo ještě nemusí. Proto se nejdřív podle pravidla 1 určí, kdo sedět musí a kdo smí, a mix polovin se řeší až uvnitř této množiny.
- Která polovina dává při lichém počtu sedících víc v 1. kole, rozhodne los. Dál se střídá.
- Poloviny se počítají z nasazení aktivních hráčů v okamžiku generování, takže po odstoupení se přepočítají.

### Generování týmů (§1.6)
- Penalizace:
  - spoluhráči: `10 · c²`, kde c = počet dosavadních zápasů ve stejném týmu,
  - soupeři: `3 · c`,
  - top 3: `50` za každou dvojici ve stejném týmu,
  - nasazení: `20 · max(0, |Δ průměrů| − 2)²`.
- Při 10 hrajících se projdou všechna rozdělení (126), jinak K náhodných.
- Každý z M běhů má vlastní seed odvozený z hlavního a generuje znovu i plán sezení. Uloží se seed vítězného běhu.

### Rozpis, kola a fáze (§1.7, §2.3)
- **„Publikovat“ je zároveň přechod `schedule_review → group_stage`.** Samostatná publikace bez startu nic nepřináší.
- Návrat o fázi zpět je povolený jen tam, kde se neztratí nic odehraného: `ranking → registration` (pozdní registrace) a `schedule_review → ranking` (zahodí rozpis i snapshot nasazení).
- V registraci a hodnocení může admin hráče přejmenovat nebo smazat (překlepy, duplicity).
- Stav kola: `planned` se automaticky mění na `in_progress` s prvním výsledkem. `done` nastaví admin uzavřením, a to jen když mají všechny zápasy výsledek. Aktuální kolo je první neuzavřené.

### Odstoupení (§1.8)
- Kolo X musí být bez jediného výsledku, protože rozehrané kolo se nesmí měnit.
- Přegenerují se jen kola od X dál. Kola před X zůstanou, jak jsou, a jejich sezení i spoluhráči se počítají do historie.

### Shody (§1.9, §1.11)
- Hranice postupu (10./11.) a kapitáni se počítají jen mezi aktivními hráči. Odstoupivší zůstávají v tabulce se svými body, ale jsou označení a do pořadí pro postup se nepočítají.
- Rozstřel o postup určuje jen to, kdo je nad čarou. Pořadí v rámci skupiny se pak dál řídí nasazením.
- Uložený výsledek rozstřelu platí jen pro přesně stejnou skupinu shodných hráčů. Když oprava výsledku skupinu změní, shoda se znovu objeví jako nevyřešená.
- Rozstřel o šampiona při 3 a více hráčích: aplikace vylosuje a zobrazí pavouka, admin zadá vítěze.
- **Zadávání rozstřelu o postup se přesouvá do milníku 5.** Přechod do finále (milník 7) je bez něj nemožný. Rozstřel o šampiona zůstává v milníku 8.

### Finále (§1.10)
- Výhodu (b) „strana a první pick na 1. mapě“ chápu doslova: kapitán s touto výhodou volí na 1. mapě stranu i první pick zároveň. *(Pokud se myslelo „stranu, nebo první pick“, je to malá změna.)*
- Admin může v draftu i při volbě rolí udělat tah za kohokoli (když někdo nemá telefon po ruce) a vrátit poslední tah.
- Volba rolí běží v obou týmech souběžně, v každém týmu podle umístění jeho hráčů v základní části.
- Výsledky map zadává admin. Killy jsou ve finále nepovinné, protože body neovlivňují.

### Přihlášení (§2.2 a sekce 0)
- Admin a hráč můžou být ve stejné session současně (organizátor může i hrát).
- Rate limit: login kódem 10 pokusů/min, admin login 5 pokusů/min (na IP).
- Test anonymity projde všechny GET routy jako každá role (nepřihlášený, hráč, admin, TV) a ověří, že v odpovědi nejsou cizí hodnocení. U hráče a TV ověří i to, že v ní není nasazení.

## 4. Nasazení (§2.7)

- **Doména v repozitáři být nemusí.** `compose.prod.yaml` má v labelech `caddy: ${APP_DOMAIN}` a skutečná hodnota je jen v `.env` na serveru, stejně jako `APP_KEY`, `REGISTRATION_CODE`, `ADMIN_PASSWORD` a `TV_KEY`.
- GitHub Actions:
  - `ci.yml` (push + PR): Pint, Larastan, ESLint, `tsc`, Pest, build obrazu.
  - `deploy.yml` (push do `main` po úspěšném CI): obraz do `ghcr.io/devrosnicka/dota-tournament`, pak přes SSH na serveru `docker compose pull && docker compose up -d`.
  - Přístup na server je v GitHub Secrets (`SSH_HOST`, `SSH_USER`, `SSH_KEY`, `DEPLOY_PATH`). Secrets nejsou vidět ani u veřejného repa.
- Obraz na GHCR bude veřejný, protože neobsahuje žádná tajemství. Server se pak nemusí do registru přihlašovat.
- Entrypoint kontejneru spustí `migrate --force` a nacachuje konfiguraci a routy.
- Jeden volume pro `storage/` včetně SQLite souboru. Záloha přes `sqlite3 .backup`, což je bezpečné i za běhu ve WAL režimu (připravím skript).

## 5. Milníky

Každý milník končí zelenými testy. Stav k 2026-10-08: milníky 1–8 jsou hotové.

1. **Kostra:** starter kit bez účtů, Pest, Docker (dev + prod), CI a deploy workflow, čeština, layout pro hráče, `players`, `settings`, `audit_log`. Layout admina přibude v milníku 2, TV v milníku 5.
2. **Hráči a přihlášení:** registrace, login kódem, admin login, správa hráčů, přechod `registration → ranking`, feature testy přístupu.
3. **Hodnocení:** drag & drop, `SeedingService`, admin přehled nasazení a kdo ještě neodeslal, přechod se snapshotem, testy anonymity.
4. **Rozpis:** `FormatResolver`, `SitPlanner` (property testy), `ScheduleGenerator`, admin náhled se statistikami, prohození, publikace, hráčský rozpis.
5. **Základní část:** zadávání výsledků, uzavírání kol, `GroupStandings` s detekcí shod, zadání výsledků rozstřelů, TV režim.
6. **Odstoupení:** odstoupení a regenerace rozpisu.
7. **Finále:** volba výhody, živý draft, role, Bo3.
8. **Konec:** `OverallStandings`, rozstřel o šampiona, vyhlášení trofejí, TV pro konec turnaje.

## 6. Pracovní postup

- Commity průběžně. Na konci milníku se zelenými testy push do `main`.
- Deploy: v milníku 1 vznikne CI i deploy workflow. Údaje o serveru (GitHub Secrets, `.env` na serveru, subdoména) dodá uživatel později, k tomu bude krátký návod v README.
