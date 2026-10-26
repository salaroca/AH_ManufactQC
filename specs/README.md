# Specs (Spec-Driven Development)

Cada funcionalitat nova o canvi de regles de negoci té una carpeta `specs/NNN-nom-curt/` (NNN = número següent, amb 3 xifres):

| Fitxer | Qui l'escriu | Contingut |
|---|---|---|
| `spec.md` | `planner` | El QUÈ i el PER QUÈ: històries d'usuari, requisits EARS, criteris Gherkin. Sense codi. |
| `plan.md` | `planner` | El COM: fitxers, decisions i alternatives, estratègia de tests. |
| `tasks.md` | `planner` (l'`implementer` les marca) | Màxim 10 tasques ordenades, amb "Fet quan:". |

Flux complet: skill `/sdd`. Plantilles: `specs/_plantilla/`.
Canvis petits que no necessiten spec: `/feature`.
