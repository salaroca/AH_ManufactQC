# CLAUDE.md

Context principal per a Claude Code en aquest repositori. Ha de ser **curt**: regles i on trobar les coses. El detall va als fitxers enllaçats.

## Missió
AH_ManufactQC és un sistema digital de control de qualitat per a inspeccions de fabricació. Els **Operaris** (tauleta) responen qüestionaris Sí/No/Defecte per a cada número de sèrie, registren defectes i fan fotos. **QC/Admin** (escriptori) gestionen projectes, OF, equips, preguntes i usuaris, i revisen el Dashboard. L'objectiu actual és una demo venible i preparar el projecte definitiu.

## Fonts de veritat (llegeix-les abans de dissenyar o implementar)
| Què | On |
|---|---|
| Principis que no es poden trencar | `docs/constitution.md` |
| Estat actual, lliçons i specs fetes | `MEMORY.md` |
| Esquema de la BD | `context/schema.sql` (generat; la font real són les migracions) |
| Regles de negoci, model de dades i full de ruta del definitiu | `docs/projecte-definitiu.md` |
| El *perquè* de cada decisió passada (incidents, iteracions) | `docs/history.md` |
| Especificacions de funcionalitats | `specs/NNN-nom/{spec,plan,tasks}.md` |

## Regla d'or
Explicar → esperar aprovació explícita → implementar amb tests → confirmar tests en verd. Res que no sigui trivial s'implementa sense un "sí".

## Flux de treball
- **Funcionalitat nova o canvi de regles de negoci:** `/sdd` (spec → pla → tasques, amb els subagents `planner`, `implementer` i `reviewer`).
- **Canvi petit** (un text, un estil, un bug acotat): `/feature`.
- **Abans de qualsevol canvi que pugui invalidar dades:** `/db-backup`.
- Una branca per a cada spec o canvi. Els commits els fa l'usuari (constitució §4).

## Stack
Laravel 13 + Inertia (v3) · Vue 3 + Vite · Tailwind CSS **v3** (no v4) · SQLite (`database/database.sqlite`) · vue-i18n (ca/es) · `@lucide/vue` · Pest 4 + Vitest · sessions de Laravel (Admin/QC entren amb email, Operari amb usuari) · fotos al disc privat `photos` (`storage/app/photos`).

## Convencions
- **PHP:** variables en `camelCase`, classes en `PascalCase`, constants en `SCREAMING_SNAKE_CASE`. Validació només en Form Requests. Relacions Eloquent.
- **Vue/JS:** components en `PascalCase`, variables en `camelCase`. Tot el text via `$t()` (`resources/lang/{ca,es}.json`). Crides a l'API via `resources/js/api.js` (fetch + XSRF, sense axios).
- **Carpetes:** `app/Http/{Controllers,Middleware}/{Admin,Operari}/`, `app/Http/Requests/`, `resources/js/Pages/{Admin,Operari}/`, `resources/js/Components/`, `resources/js/__tests__/`, `tests/Feature/`.
- **Rutes:** `/api/*` (Admin/QC) i `/operari/api/*` (Operari) passen pel middleware de sessió `web` amb guarda de rol. No fan servir el grup `api` sense estat.
- **Terminologia de la interfície:** la `Section` es diu "Part de descripció" i el seu camp `description` es diu "Detall". El codi no es reanomena.
- **Comunicació:** sempre en català.

## Comandes
```bash
php artisan serve                 # backend (port 8000)
npm run dev                       # Vite
./vendor/bin/pest                 # tests de backend
npx vitest run                    # tests de frontend
./vendor/bin/pint                 # format PHP
# Regenerar l'esquema de context després d'una migració:
sqlite3 database/database.sqlite .schema | grep -v sqlite_sequence  # → context/schema.sql (manté la capçalera)
```
