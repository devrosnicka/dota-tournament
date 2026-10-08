# Dota LAN turnaj – zadání aplikace

> Zadání pro implementaci. Sekce 1 popisuje pravidla turnaje (doménová logika), sekce 2 technické řešení.

## 0. Kontext

Webová aplikace pro řízení Dota 2 turnaje na LAN party přátel. Hraje cca 10–16 hráčů, přesný počet není předem známý. Aplikace řeší registraci, vzájemné hodnocení hráčů, generování rozpisu, zadávání výsledků, tabulky, draft finále a vyhlášení. Samotné hraní (lobby v Dotě) aplikace neřeší.

- UI česky, mobile-first (hráči používají hlavně mobily) + režim pro projektor/TV.
- Časová zóna `Europe/Prague`.
- Běží online na vlastním serveru, přístup jen pro účastníky.

---

## 1. Pravidla turnaje

### 1.1 Průběh

1. Registrace
2. Vzájemné hodnocení → nasazení
3. Vygenerování a zveřejnění celého rozpisu (všechna kola najednou)
4. Základní část (výchozí 5 kol)
5. Draft finále (kapitáni, výběr hráčů, výběr rolí)
6. Finále 5v5, Bo3
7. Vyhlášení: **Vítězný tým** a **Celkový šampion**

### 1.2 Vzájemné hodnocení a nasazení

- Každý hráč seřadí všechny ostatní hráče od nejlepšího po nejhoršího, sebe nehodnotí. Pozice 1 = nejlepší, N−1 = nejhorší.
- Pořadí lze měnit, dokud admin fázi neuzavře. Odeslání není povinné.
- Agregace pro každého hráče: vezmi všechny pozice, které mu dali ostatní.
  - Při 5 a více hodnoceních odstraň jedno nejlepší a jedno nejhorší a spočítej průměr (oříznutý průměr).
  - Při méně hodnoceních použij prostý průměr.
- Nasazení = vzestupné pořadí podle tohoto průměru (1 = nejsilnější). Při shodě rozhodne prostý průměr, pak los.
- Při uzavření fáze se nasazení uloží jako snapshot. Rozpis z něj vychází a později se nesmí měnit.
- **Anonymita:** jednotlivá hodnocení nikdy neopustí server. Hráč vidí jen svoje vlastní řazení. Agregované nasazení vidí jen admin.

### 1.3 Formát základní části podle počtu hráčů

Formát se určuje pro každé kolo podle počtu aktivních hráčů v daném kole:

| Aktivních hráčů | Zápasy v kole | Sedí v kole |
|---|---|---|
| 10 | 1× 5v5 | 0 |
| 11 | 1× 5v5 | 1 |
| 12 | 2× 3v3 (paralelně) | 0 |
| 13 | 2× 3v3 | 1 |
| 14 | 2× 3v3 | 2 |
| 15 | 2× 3v3 | 3 |
| 16 | 2× 4v4 | 0 |

- Mimo rozsah 10–16 aplikace generování rozpisu odmítne s chybou.
- Počet kol nastavuje admin před generováním (výchozí 5).
- Pod 10 aktivních hráčů se finále nehraje a aplikace tento případ nepodporuje.

### 1.4 Bodování základní části

- Výhra 1 bod, prohra 0 bodů, sezení 0,5 bodu.
- Výsledek zápasu: vítězný tým a killy obou týmů (ze skóre ve hře).
- Rozdíl killů hráče = součet (killy vlastního týmu − killy soupeře) přes všechny jeho odehrané zápasy. Sezení přidává 0.
- Pořadí v tabulce: body → rozdíl killů → shody podle 1.9.

### 1.5 Plán sezení

Generuje se pro všechna kola najednou, ještě před týmy.

1. **Rovnoměrnost (tvrdé pravidlo):** po každém kole se počet sezení mezi hráči liší nejvýš o 1. Nikdo tedy nesedí podruhé, dokud všichni neseděli aspoň jednou. Pokud to jde, nesedí nikdo ve dvou po sobě jdoucích kolech.
2. **Míchání úrovní (měkké pravidlo):** hráči se podle nasazení rozdělí na horní a spodní polovinu (při lichém počtu má horní polovina o jednoho víc). Sedí-li v kole 2 a více hráčů, mají být z obou polovin co nejrovnoměrněji. Při lichém počtu sedících se po kolech střídá, která polovina dává o jednoho víc.
3. **Jinak los:** kdo sedí víckrát a kdo vůbec, rozhoduje náhoda, nikdy nasazení.

Doporučený algoritmus: procházej kola postupně. Kandidáty seřaď podle dosavadního počtu sezení (náhodně v rámci stejného počtu). Z každé poloviny vyber potřebný počet kandidátů s nejnižším počtem sezení. Když v polovině nestačí, doplň z druhé.

### 1.6 Generování týmů

Pro každé kolo se hrající hráči rozdělí do zápasů a týmů. Týmy se vyvažují **jen měkce**. Cílem je vyhnout se jednostranným zápasům, ne přesně srovnat síly, jinak by se tabulka změnila v loterii.

Cena kandidátního rozdělení = součet penalizací:

| Penalizace | Výchozí váha |
|---|---|
| Dvojice spoluhráčů, kteří spolu už hráli (kvadraticky podle počtu společných zápasů) | 10 |
| Dvojice soupeřů, kteří proti sobě už hráli | 3 |
| Dva hráči z top 3 nasazení ve stejném týmu | 50 |
| Rozdíl průměrného nasazení týmů v zápase nad toleranci (výchozí 2,0); kvadraticky za každou jednotku nad tolerancí | 20 |

Role hráčů se při generování týmů nezohledňují.

**Algoritmus:**
- Kola generuj postupně. Pro každé kolo vygeneruj K náhodných rozdělení (výchozí 5000) a vyber nejlevnější. U malých případů (např. 10 hráčů = 126 rozdělení) lze projít všechna.
- Historie spoluhráčů a soupeřů se přenáší do dalších kol.
- Celé generování spusť M-krát (výchozí 20) s různým seedem a vezmi rozpis s nejnižší celkovou cenou.
- Váhy, toleranci, K a M drž v konfiguraci.
- Ulož RNG seed, aby šel rozpis reprodukovat.

### 1.7 Zveřejnění rozpisu

- Admin vidí náhled: všechna kola, zápasy, týmy a sedící hráče. K tomu statistiky: maximální opakování spoluhráčů, rozdíl nasazení v každém zápase a počet sezení na hráče.
- Admin může rozpis přegenerovat, nebo v kole ručně prohodit dva hráče (mezi týmy, nebo hrajícího se sedícím). Aplikace upozorní, pokud tím poruší pravidla sezení.
- Po publikaci vidí celý rozpis všichni hráči, svoje zápasy a sezení mají zvýrazněné.

### 1.8 Odstoupení

- Admin označí hráče jako odstoupivšího od kola X. Neodehraná kola se přegenerují: sezení, týmy i formát podle nového počtu hráčů (např. 12 → 11 znamená přechod z 3v3 na 5v5).
- Přegenerovávají se jen kola, která ještě nemají žádný výsledek. Odehraná a rozehraná kola se nikdy nemění.
- Odstoupivší hráč si ponechá získané body, ale do finále nepostupuje.
- Pozdní příchody se neřeší: zápas se nespustí, dokud nejsou přítomni všichni jeho hráči. Registrace se uzavírá před začátkem turnaje.

### 1.9 Shody v tabulce základní části

Pokud po bodech a rozdílu killů zůstane shoda:

- Když shoda rozhoduje **o postupu do finále** (hranice 10./11. místa) nebo **o kapitánech** (hranice 2./3. místa), hraje se rozstřel 1v1 se Shadow Fiendem v herním módu 1v1 Solo Mid.
- Ostatní shody rozhodne los. Výsledek losu se uloží, aby se pořadí neměnilo při každém načtení.
- Aplikace shody, které vyžadují rozstřel, sama detekuje a zobrazí adminovi. Admin po odehrání rozstřelu zadá výsledné pořadí shodných hráčů.

### 1.10 Finále

- Postupuje top 10 aktivních hráčů základní části. Při 10 hráčích postupují všichni.
- **Kapitáni:** 1. a 2. místo základní části.
- **Výhoda:** hráč na 1. místě si vybere jednu ze dvou výhod. Druhý kapitán automaticky dostane tu druhou.
  - (a) první výběr hráče, nebo
  - (b) strana a první pick v hero draftu na 1. mapě.
- **Výběr hráčů:** hadové pořadí A, B, B, A, A, B, B, A, kde A je kapitán s prvním výběrem. Probíhá živě: kapitáni vybírají na mobilu, ostatní sledují na TV.
- **Výběr rolí:** v každém týmu si hráči včetně kapitána volí pozici 1–5 v pořadí podle umístění v základní části. Každá pozice je v týmu jen jednou.
- **Série Bo3**, 3. mapa jen při stavu 1:1. Na 2. a 3. mapě si tým, který prohrál předchozí mapu, vybere stranu, nebo první pick; druhý tým dostane to druhé. Aplikace ukazuje, kdo volí, a stranu i první pick zaznamenává (jen informativně).
- **Body z finále** pro každého hráče: +1 za každou mapu vyhranou jeho týmem a +1 za výhru v sérii. Výsledek 2:0 tedy dá vítězům 3 body a poraženým 0, výsledek 2:1 vítězům 3 a poraženým 1.

### 1.11 Celkové pořadí a trofeje

- Celkové body = body ze základní části + body z finále.
- Shoda o 1. místo se rozhoduje takto:
  1. body z finále,
  2. rozstřel 1v1 se Shadow Fiendem; při 3 a více shodných hráčích pavouk s nasazením losem.

  Admin zadá vítěze rozstřelu. Ostatní shody v celkovém pořadí jsou sdílené.
- Trofeje:
  - **Vítězný tým**: vítězové finále.
  - **Celkový šampion**: 1. místo celkového pořadí.

---

## 2. Technické zadání

### 2.1 Stack a architektura

- Laravel (aktuální verze) + Inertia.js + React + TypeScript
- Tailwind + shadcn/ui
- SQLite (soubor na Docker volume)
- dnd-kit pro řazení hráčů (drag & drop, musí fungovat na dotyku)
- Testy: Pest
- Žádné websockety, jen polling (Inertia `usePoll`): TV a tabulky po 10 s, draft po 2–3 s.

Doménová logika patří do čistých PHP služeb bez závislosti na HTTP vrstvě a musí být plně pokrytá unit testy:

- `SeedingService`: agregace hodnocení, snapshot nasazení
- `FormatResolver`: počet hráčů → formát kola
- `SitPlanner`: plán sezení
- `ScheduleGenerator`: týmy a zápasy
- `GroupStandings`: tabulka základní části, detekce shod
- `FinalDraft`: kapitáni, výhody, pořadí výběru, role
- `OverallStandings`: celkové pořadí, šampion

**Body a pořadí se nikdy neukládají**, vždy se dopočítávají ze zápasů. Oprava výsledku se tak automaticky promítne všude.

### 2.2 Autentizace a autorizace

- **Registrace:** přezdívka (unikátní) a registrační kód z `.env` (`REGISTRATION_CODE`), který znají jen účastníci (aplikace běží veřejně online). Registrace je otevřená jen ve fázi `registration`.
- **Login odkazem:** po registraci se vygeneruje náhodný token (32 B). V DB se ukládá jen jeho SHA-256 hash. Hráč dostane osobní odkaz `/login/{token}` a po jeho otevření se `player_id` uloží do session.
- **Session:** standardní Laravel session (šifrovaná a podepsaná cookie), životnost 30 dní, `secure` cookie.
- **Admin:** heslo z `.env` (`ADMIN_PASSWORD`), porovnání přes `hash_equals`, příznak admina v session. Žádné uživatelské účty.
- Admin vidí u každého hráče QR kód s login odkazem (pro přenos na jiné zařízení) a může token přegenerovat.
- **TV režim:** `/tv?key=...` (`TV_KEY` v `.env`), jen pro čtení, bez přihlášení.
- Rate limit na `/login/{token}` a na admin login.
- **Kritické:** surová data z tabulky `rankings` se nikdy neposílají do Inertia props ani do žádné odpovědi. Výjimkou je vlastní řazení přihlášeného hráče. Nasazení vidí jen admin. Na obojí musí existovat test.

### 2.3 Fáze turnaje (stavový automat)

```
registration → ranking → schedule_review → group_stage → final_draft → final → finished
```

Přechody spouští jen admin. Každý přechod má kontroly:

| Přechod | Kontrola / akce |
|---|---|
| `registration → ranking` | registrace se uzavře |
| `ranking → schedule_review` | hodnocení se uzavře, uloží se snapshot nasazení, počet aktivních hráčů musí být 10–16 |
| `schedule_review → group_stage` | rozpis je vygenerovaný a publikovaný |
| `group_stage → final_draft` | všechna kola uzavřená, shody vyžadující rozstřel vyřešené, aspoň 10 aktivních hráčů |
| `final_draft → final` | draft hráčů i rolí je dokončený |
| `final → finished` | série je rozhodnutá, případný rozstřel o šampiona vyřešený |

### 2.4 Datový model

| Tabulka | Sloupce |
|---|---|
| `players` | id, nick (unique), login_token_hash, status (`active`/`withdrawn`), withdrawn_from_round (nullable), seed_rank, seed_score (snapshot), timestamps |
| `rankings` | rater_id, ratee_id, position; unique(rater_id, ratee_id) |
| `rounds` | id, number, format (`5v5`/`3v3`/`4v4`), status (`planned`/`in_progress`/`done`) |
| `round_sits` | round_id, player_id |
| `matches` | id, stage (`group`/`final`/`tiebreak`), round_id (nullable), lobby (nullable), map_number (nullable), winner (`A`/`B`/null), kills_a, kills_b, radiant_side (nullable), first_pick_side (nullable), reported_by, reported_at |
| `match_players` | match_id, player_id, side (`A`/`B`) |
| `final_setup` | captain1_id (1. místo), captain2_id, advantage_choice (`player_pick`/`side_pick`) |
| `final_picks` | pick_number, captain_id, player_id |
| `final_roles` | player_id, position |
| `tiebreak_orders` | context (`qualification`/`captains`/`champion`), ordered_player_ids (json), note |
| `settings` | key/value: fáze, počet kol, RNG seed, váhy generátoru |
| `audit_log` | actor (player_id nebo admin), action, payload (json), created_at |

### 2.5 Obrazovky

**Hráč (mobile-first)**
- Registrace; přihlášení osobním odkazem
- Hodnocení: drag & drop seznam ostatních hráčů, uložení, indikace „uloženo“
- Rozpis: všechna kola, moje zápasy zvýrazněné, kdy sedím
- Zápas: zadání výsledku (vítěz + killy obou týmů). Smí ho zadat kterýkoli hráč daného zápasu. Dokud admin kolo neuzavře, lze výsledek přepsat. Každý zápis jde do audit logu.
- Tabulka základní části: body, výhry/prohry, sezení, rozdíl killů
- Finále: draft (kapitán vybírá, když je na tahu), volba role, stav série
- Výsledky: celkové pořadí, trofeje

**Admin**
- Řízení fází a přechodů
- Hráči: seznam, QR kódy / login odkazy, přegenerování tokenu, odstoupení, přehled, kdo ještě neodeslal hodnocení
- Nasazení (agregované)
- Rozpis: generování, náhled se statistikami, přegenerování, prohození dvou hráčů, publikace
- Výsledky: oprava libovolného zápasu, uzavření kola
- Shody: detekované shody vyžadující rozstřel, zadání výsledného pořadí
- Finále: zadání výsledků map, vrácení posledního tahu draftu

**TV (`/tv`)**: obsah se mění podle fáze, velké písmo, polling po 10 s.
- registrace: přihlášení hráči
- hodnocení: kolik hráčů už odeslalo (bez obsahu)
- základní část: aktuální kolo (zápasy, týmy, kdo sedí) + tabulka
- draft: živý výběr hráčů a rolí
- finále: složení týmů, stav série
- konec: trofeje + celkové pořadí

### 2.6 Souběh v draftu

Server v transakci ověří, že vybírající je na tahu a že vybraný hráč je volný. Neplatný tah vrátí chybu a klient se obnoví. Admin může poslední tah vrátit.

### 2.7 Nasazení a provoz

- Jeden kontejner (php-fpm + nginx, nebo FrankenPHP). SQLite a `storage` na volume.
- Docker Compose v lokální a produkční variantě.
- Produkce: externí Docker síť `caddy_net`, routování na subdoménu přes labely `caddy-docker-proxy` (subdoménu doplnit).
- CI/CD: GitHub Actions → image do GHCR → deploy na server přes SSH.
- Migrace se spouští při startu kontejneru.
- Záloha = kopie SQLite souboru.

### 2.8 Testy (akceptační kritéria)

**Unit testy**
- `SeedingService`: oříznutý průměr, prostý průměr při méně než 5 hodnoceních, řešení shod.
- `FormatResolver`: tabulka z 1.3, chyba mimo rozsah 10–16.
- `SitPlanner`: property test pro N = 10–16, R = 1–8 a mnoho seedů:
  - každé kolo má správný počet sedících,
  - rozdíl počtu sezení je po každém kole nejvýš 1,
  - sedící jsou z obou polovin nasazení,
  - stejný seed dá stejný výsledek.
- `ScheduleGenerator`:
  - každý aktivní hráč je v každém kole právě jednou (hraje, nebo sedí),
  - velikosti týmů odpovídají formátu,
  - u běžných vstupů nejsou dva hráči z top 3 v jednom týmu.
- `GroupStandings`: body, sezení za 0,5, rozdíl killů, řazení, detekce shod na hranicích 2./3. a 10./11. místa.
- `FinalDraft`: hadové pořadí výběru, přiřazení výhod, unikátnost rolí v týmu.
- `OverallStandings`: bonusy při 2:0 a 2:1, rozhodování o šampionovi.
- Regenerace: po odstoupení se změní jen kola bez výsledků; změna formátu při 12 → 11 hráčích.

**Feature testy**
- Surová hodnocení se nikdy neobjeví v žádné odpovědi.
- Hráč nemůže zadat výsledek zápasu, ve kterém nehraje.
- Registrace bez kódu nebo mimo fázi registrace selže.
- Nepřihlášený uživatel neuvidí nic kromě registrace, přihlášení, průběhu turnaje a TV režimu se správným klíčem.

### 2.9 Postup implementace (milníky)

Každý milník končí procházejícími testy.

1. Kostra projektu: Laravel + Inertia + React + TS + shadcn/ui, SQLite, Docker (lokál i produkce), CI
2. Registrace, login odkazem, admin login, správa hráčů s QR kódy
3. Vzájemné hodnocení + agregace nasazení
4. `FormatResolver`, `SitPlanner`, `ScheduleGenerator` + admin náhled, úpravy a publikace rozpisu
5. Zadávání výsledků, tabulka základní části, TV režim
6. Odstoupení a regenerace rozpisu
7. Finále: kapitáni, volba výhody, živý draft, volba rolí, Bo3
8. Celkové pořadí, rozstřely, vyhlášení trofejí
