# MEMORY — memòria viva del projecte

Estat actual i lliçons apreses, compartit per tots els agents. **L'implementador l'actualitza en acabar l'última tasca de cada spec.** Ha de ser curt: el detall va a `docs/history.md`, i les regles fixes a `docs/constitution.md`.

## Estat actual (26/10/2026)
- Prototip funcional complet (les 5 fases originals fetes): flux d'Operari + CRUD d'Admin + Dashboard. 166 tests de Pest i 10 de Vitest en verd.
- Prioritat de negoci: una **demo venible**. Els catàlegs d'habilitats/departaments i les traduccions de catàlegs estan **ajornats** fins que l'usuari els demani.
- Projecte definitiu en preparació: vegeu `docs/projecte-definitiu.md` (Bootstrap, API, ERP, mòduls per departament i preguntes obertes).
- Pendent conegut: el login no té límit d'intents (spec prevista: `001-rate-limit-login`).

## Specs
| Núm. | Nom | Estat |
|---|---|---|
| — | (encara cap; la plantilla és a `specs/_plantilla/`) | — |

## Lliçons apreses (resum; el detall és a `docs/history.md`)
- **Bucle de crides sense límit** ("Generar equips en sèrie") → es van crear milers d'equips per error. Ara hi ha límit + progrés + cancel·lar (constitució §17).
- **L'estat de l'equip va necessitar 3 iteracions.** La regla bona és asimètrica: mentre es revisa, segueix les respostes actuals; un cop finalitzat, segueix l'historial de `Defect`. Cal demanar la regla completa (§9).
- **Recàlculs dispersos** per controller → estats obsolets. Solució: `Equipment::refreshStatus()` (§8).
- **Dades parcials en reobrir** un equip → es perdien els defectes a la pantalla (§10).
- **Solapament amb un element `fixed`:** no era el z-index sinó coordenades; cal mesurar (§19).
- **Efecte de pressió invisible** amb un 5% → cal un 10% (§18).
- **Navegació oblidada** dues vegades → cal construir-la des del principi (§15).
- **Condició de carrera en "Finalitzar"** → el botó queda desactivat mentre hi ha mutacions pendents (`pendingMutations`).

## Terminologia de la interfície
- La `Section` es diu **"Part de descripció"** a la interfície. El seu camp `description` es mostra com a **"Detall"**. El codi, les rutes i la BD mantenen `section`.
- "Accés Qualitat" és l'àrea d'Operari. Admin i QC hi poden entrar sense un segon login.
