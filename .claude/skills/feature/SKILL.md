---
name: feature
description: Flux curt per a canvis petits d'AH_ManufactQC que no necessiten una spec (un text, un estil, un bug acotat). Explicar, esperar aprovació, implementar amb tests i verificar.
---

# Canvi petit (sense spec)

1. **Comprova que realment és petit:** si canvia regles de negoci, el model de dades o més de 3-4 fitxers, proposa `/sdd`.
2. **Branca:** si som a `main`, crea `fix/nom-curt` o `chore/nom-curt`.
3. **Explica** a l'usuari què canviaràs i a quins fitxers. **Espera la seva aprovació** (regla d'or).
4. **Implementa'l amb test:** si és un bug, primer un test que el reprodueixi (en vermell) i després la correcció. Textos nous a `ca.json` i `es.json`.
5. **Verifica:** `./vendor/bin/pest`, `npx vitest run` si toca el frontend, `./vendor/bin/pint`, i comprovació visual si hi ha canvis d'interfície.
6. **Tanca:** resumeix el canvi i proposa una descripció de commit. **No facis el commit.** Si n'ha sortit una lliçó nova, afegeix-la a `MEMORY.md`.
