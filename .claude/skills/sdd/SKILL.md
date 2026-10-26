---
name: sdd
description: Flux Spec-Driven Development d'AH_ManufactQC. La sessió principal fa de coordinadora i reparteix la feina entre els subagents planner, implementer i reviewer, aturant-se a cada aprovació de l'usuari. Fes-lo servir per a funcionalitats noves o canvis de regles de negoci.
---

# Flux SDD (coordinador)

Fas de **coordinador**: no escrius la spec ni el codi tu mateix, sinó que reparteixes la feina entre els subagents i parles amb l'usuari, sempre en català. Abans de cada fase, digues a l'usuari en una línia en quina fase entres.

Si la petició és un canvi petit que no necessita una spec (un text, un estil, un bug acotat), proposa fer servir `/feature`.

## Fases
0. **Branca:** si som a `main`, crea `feature/NNN-nom` (amb el número següent lliure de `specs/`).
1. **Spec:** demana a `planner` que redacti `specs/NNN-nom/spec.md`. Si retorna preguntes, fes-les a l'usuari **d'una en una** i torna a cridar el planner amb les respostes.
2. **Clarificació:** demana a `reviewer` que revisi la spec com a QA. Ensenya el resultat a l'usuari; si hi ha problemes, el `planner` corregeix la spec. **ATURA'T fins que l'usuari aprovi la spec** (l'estat passa a `aprovada`).
3. **Pla i tasques:** demana a `planner` els fitxers `plan.md` i `tasks.md`. Ensenya'n un resum i **ATURA'T fins que l'usuari els aprovi.**
4. **Còpia de seguretat:** si el pla toca dades existents, executa `/db-backup` abans d'implementar.
5. **Implementació:** crida `implementer` **una vegada per tasca** (T1, T2…), en ordre. Després de cada tasca, comprova que Pest (i Vitest, si toca) està en verd; si no ho està, atura't i avisa l'usuari.
6. **Validació:** demana a `reviewer` que validi la spec RF per RF.
7. **Correccions:** si el veredicte és `CANVIS NECESSARIS`, torna a `implementer` amb la llista exacta i després torna a `reviewer`. **Com a màxim 2 voltes**; si encara falla, atura't i explica a l'usuari què passa.
8. **Verificació visual:** si hi ha canvis d'interfície, comprova-ho tu mateix al navegador (375 / 768 / 1440 px).
9. **Tancament:** resumeix què s'ha fet, el veredicte del revisor i el que queda pendent. Proposa una descripció de commit, però **no facis el commit**.

## Canvis de requisits sobre una spec existent
Primer el `planner` actualitza `spec.md` i n'ensenyes el diff. Amb l'aprovació de l'usuari, s'actualitzen `plan.md` i `tasks.md`, i després s'implementa.

## Transmetre el context
Els subagents **no veuen aquesta conversa**. A cada crida passa'ls:
- La fase i què s'espera d'ells.
- La petició original de l'usuari, amb les seves paraules, i les decisions que ha pres.
- Les rutes dels fitxers que han de llegir (spec, pla, tasques, fitxers modificats).
- El resultat de la fase anterior.

## Regles
- Mai no et saltis cap aprovació de l'usuari (la spec, i el pla amb les tasques).
- No resolguis tu els dubtes: pregunta a l'usuari.
- Pots llançar diversos `reviewer` en paral·lel amb focus diferents (constitució, interfície, cobertura de tests) i ajuntar-ne els resultats en un sol informe sense duplicats.
