# AH_ManufactQC

Sistema digital de control de qualitat per a inspeccions de fabricació.

- **Accés Qualitat (Operari, tauleta):** cerca una OF, tria un número de sèrie i respon el qüestionari Sí/No/Defecte. Pot registrar defectes (tipus, observació, responsabilitat, accions) i fer fotos en finalitzar.
- **Administració (Admin/QC, escriptori):** projectes, ordres de fabricació, equips, parts de descripció, banc de preguntes, categories, usuaris i un Dashboard amb estadístiques de defectes.

## Stack
Laravel 13 · Inertia · Vue 3 · Vite · Tailwind CSS 3 · SQLite · vue-i18n (català/castellà) · Pest + Vitest.

## Posada en marxa
```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
php artisan serve      # http://localhost:8000
npm run dev
```

## Tests
```bash
./vendor/bin/pest
npx vitest run
```

## Documentació
| Fitxer | Contingut |
|---|---|
| `CLAUDE.md` | Context per a l'assistent d'IA: missió, regles, convencions i comandes |
| `docs/constitution.md` | Principis que no es poden trencar |
| `MEMORY.md` | Estat actual i lliçons apreses |
| `docs/projecte-definitiu.md` | Regles de negoci i full de ruta del projecte definitiu |
| `docs/history.md` | Historial detallat de decisions i incidents |
| `context/schema.sql` | Esquema de la base de dades |
| `specs/` | Especificacions de funcionalitats (Spec-Driven Development) |
| `.claude/` | Subagents (`planner`, `implementer`, `reviewer`) i skills (`/sdd`, `/feature`, `/db-backup`) |
