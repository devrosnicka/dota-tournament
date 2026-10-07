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

## Nasazení

Každý push do `main` spustí [CI](.github/workflows/ci.yml):

1. **checks**: styl, statická analýza, lint, typy, testy,
2. **image**: produkční obraz do `ghcr.io/devrosnicka/dota-tournament:latest`,
3. **deploy**: přes SSH na server. Běží jen při nastavené proměnné `DEPLOY_ENABLED=true`.

Doména ani tajné hodnoty v repozitáři nejsou. Jsou jen v `.env` na serveru a v GitHub Secrets, které nejsou vidět ani u veřejného repa.

### Jednorázová příprava serveru

Předpoklad: Docker s Compose a běžící [caddy-docker-proxy](https://github.com/lucaslorentz/caddy-docker-proxy) na externí síti `caddy_net`.

1. **Adresář pro nasazení** (např. `/opt/dota-tournament`) se souborem `.env`:

   ```sh
   APP_NAME="Dota LAN turnaj"
   APP_DOMAIN=turnaj.example.cz
   APP_URL=https://turnaj.example.cz
   APP_KEY=base64:...        # vygeneruj: echo "base64:$(openssl rand -base64 32)"
   REGISTRATION_CODE=...
   ADMIN_PASSWORD=...
   TV_KEY=...
   ```

2. **Deploy klíč**: na svém počítači spusť `ssh-keygen -t ed25519 -f deploy_key -N ""`. Obsah `deploy_key.pub` přidej na serveru do `~/.ssh/authorized_keys` uživatele, který smí spouštět `docker` (je ve skupině `docker`).

3. **GitHub Secrets a proměnná**:

   ```sh
   gh secret set SSH_HOST --body "server.example.cz"
   gh secret set SSH_USER --body "deploy"
   gh secret set SSH_KEY < deploy_key
   gh secret set DEPLOY_PATH --body "/opt/dota-tournament"
   gh secret set SSH_PORT --body "22"      # jen pokud SSH neběží na 22
   gh variable set DEPLOY_ENABLED --body true
   ```

4. **Viditelnost obrazu**: po prvním běhu CI přepni na GitHubu balíček `dota-tournament` na *Public* (Packages → Package settings → Change visibility). Obraz žádná tajemství neobsahuje. Druhá možnost je `docker login ghcr.io` na serveru.

Deploy pak při každém pushi zkopíruje na server [`compose.prod.yaml`](compose.prod.yaml) (jako `compose.yaml`) a [`backup.sh`](docker/backup.sh), stáhne nový obraz a restartuje kontejner. Migrace se spouští při startu kontejneru.

### Záloha

Na serveru v adresáři nasazení:

```sh
./backup.sh            # uloží konzistentní snapshot SQLite do ./backups/
```

Databáze leží na volume `dota-tournament_storage` v `/app/storage/database/database.sqlite`.
