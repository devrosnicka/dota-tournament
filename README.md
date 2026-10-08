# Dota LAN turnaj

Webová aplikace pro řízení Dota 2 turnaje na LAN party: registrace, vzájemné hodnocení, rozpis, výsledky, tabulky, draft finále a vyhlášení.

- [SPEC.md](SPEC.md) – zadání (pravidla turnaje a technické řešení)
- [PLAN.md](PLAN.md) – implementační plán, odchylky od zadání a milníky

Stack: Laravel 13, Inertia v3, React 19 + TypeScript, Tailwind 4, shadcn/ui, SQLite, Pest. V produkci FrankenPHP v Dockeru za caddy-docker-proxy.

## Vývoj

Na hostiteli stačí Docker. PHP, Composer i Node běží v dev kontejneru.

```sh
./dev setup   # dev obraz, závislosti, .env, databáze
./dev up      # aplikace na http://localhost:8000 + Vite dev server
./dev test    # Pest (např. ./dev test --filter=Phase)
./dev lint    # Pint, Larastan, lint a formát frontendu, TypeScript
./dev fix     # automatické opravy formátování
./dev check   # všechno, co pouští CI
./dev down
```

Ostatní příkazy: `./dev artisan migrate:fresh --seed`, `./dev shell`, `./dev help`.

V lokálním `.env` doplň `REGISTRATION_CODE`, `ADMIN_PASSWORD` a `TV_KEY`. Pro vývoj stačí libovolné hodnoty.

## Průběh turnaje (pro admina)

Admin se přihlásí na `/admin/login` heslem `ADMIN_PASSWORD`. Fáze přepíná na přehledu administrace.

1. **Registrace**: hráči se registrují přezdívkou a kódem `REGISTRATION_CODE`. Na jiném zařízení se přihlásí 4místným kódem (v menu „Přihlásit jiné zařízení“, nebo ho vygeneruje admin u hráče).
2. **Hodnocení hráčů**: každý seřadí ostatní. Admin vidí, kdo ještě neodeslal, a průběžné nasazení. Přechod dál zamkne nasazení (je potřeba 10–16 hráčů).
3. **Příprava rozpisu**: admin zvolí počet kol a vygeneruje rozpis. Může ho přegenerovat nebo prohodit dva hráče a pak ho tlačítkem „Zveřejnit a zahájit“ spustí.
4. **Základní část**: výsledky zadávají hráči zápasu (vítěz a killy), admin může cokoli opravit. Po každém kole ho admin uzavře v „Výsledky“. Odstoupení se zadává u hráče v „Hráči“. Shodu na hranici postupu rozhodne rozstřel, jehož výsledek se zadá v „Shody“.
5. **Draft finále**: kapitán z 1. místa zvolí výhodu, kapitáni vybírají hráče a pak si hráči volí role. Admin může táhnout za kohokoli a vrátit poslední tah.
6. **Finále**: admin zapisuje mapy série Bo3. Případnou shodu o šampiona rozhodne rozstřel („Shody“).
7. **Vyhlášení**: trofeje a celkové pořadí na stránce Výsledky a na TV.

TV režim běží na `/tv?key=TV_KEY` (jen pro čtení, obnovuje se sám).

## Nasazení

Každý push do `main` spustí [CI](.github/workflows/ci.yml):

1. **checks**: styl, statická analýza, lint, typy, testy,
2. **image**: produkční obraz do `ghcr.io/devrosnicka/dota-tournament:latest`,
3. **deploy**: přes SSH na server. Běží jen při nastavené proměnné `DEPLOY_ENABLED=true`. Ručně se dá spustit přes Actions → CI → Run workflow.

Doména ani tajné hodnoty v repozitáři nejsou. Jsou jen v `.env` na serveru a v GitHub Secrets.

### Server

Aplikace běží na stejném Hetzner VPS jako ostatní projekty, za společným Caddy z repa `hetzner-infra-proxy` (caddy-docker-proxy na síti `caddy_net`).

- Adresa: **https://dota.tomaskrizek.cz** (A záznam v Cloudflare na IP serveru, bez proxy).
- Adresář nasazení: `/opt/dota-tournament` s `.env`:

  ```sh
  APP_NAME="Dota LAN turnaj"
  APP_DOMAIN=dota.tomaskrizek.cz
  APP_URL=https://dota.tomaskrizek.cz
  APP_KEY=base64:...        # echo "base64:$(openssl rand -base64 32)"
  REGISTRATION_CODE=...
  ADMIN_PASSWORD=...
  TV_KEY=...
  ```

  Po změně `.env` stačí na serveru `docker compose up -d`.
- GitHub Secrets jsou stejné jako u ostatních aplikací na VPS: `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY` (privátní klíč `~/.ssh/hetzner_deploy`). Proměnná repozitáře `DEPLOY_ENABLED=true`.
- Obraz `ghcr.io/devrosnicka/dota-tournament` je veřejný, server se do registru nepřihlašuje.

Deploy při každém pushi zkopíruje na server [`compose.prod.yaml`](compose.prod.yaml) (jako `compose.yaml`) a [`backup.sh`](docker/backup.sh), stáhne nový obraz a restartuje kontejner. Migrace se spouští při startu kontejneru.

### Záloha

Na serveru v adresáři nasazení:

```sh
./backup.sh            # uloží konzistentní snapshot SQLite do ./backups/
```

Databáze leží na volume `dota-tournament_storage` v `/app/storage/database/database.sqlite`.

### Reset turnaje

Po zkušebním kole se dá celý turnaj smazat (hráči, hodnocení, rozpis, výsledky, finále, nastavení i audit log) a začít znovu registrací. Na serveru v adresáři nasazení:

```sh
./backup.sh
docker compose exec app php artisan tournament:reset   # zeptá se na potvrzení
```

Hráči ze zkušebního kola se tím odhlásí. Čísla hráčů pokračují dál, takže stará přihlášení nemůžou patřit novým hráčům. Admin zůstane přihlášený.
