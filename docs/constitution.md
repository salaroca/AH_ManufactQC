# Constitució del projecte AH_ManufactQC

Principis que **no es poden trencar**. Qualsevol spec, plan o implementació que hi entri en conflicte s'ha d'aturar i consultar amb l'usuari. El revisor (`.claude/agents/reviewer.md`) els comprova un per un.

## 1. Procés
1. **Regla d'or:** explicar → esperar aprovació explícita → implementar → tests en verd. Mai implementar sense aprovació.
2. **Tests obligatoris:** cada funcionalitat es lliura amb tests (Pest al backend, Vitest al frontend). Mai donar una tasca per feta amb tests en vermell.
3. **Una branca per spec o canvi** (`feature/NNN-nom`, `fix/...`, `chore/...`), mai directament a `main`.
4. **Commits:** només els fa l'usuari. "Quin és el commit?" vol dir *descriu el canvi*, no *executa `git commit`*.
5. **Idioma:** tota la comunicació i documentació en català. Els identificadors de codi es queden tal com són.
6. **Bones pràctiques de seguida:** si una opció de l'especificació és obsoleta o subòptima, se substitueix dins la mateixa tasca (i es diu), no es deixa com a nota per més endavant.

## 2. Dades
7. **Còpia de seguretat abans de cap risc:** abans de canviar un enum, eliminar una columna o un valor, o fer `migrate:fresh`, cal copiar `database/database.sqlite` (còpia binària + volcat `.sql`). La BD conté dades reals introduïdes per l'usuari. Skill: `db-backup`.
8. **Estat derivat centralitzat:** un camp calculat a partir de diverses dades (p. ex. `Equipment.status`/`checked_at`) es recalcula en **un sol mètode del model** (`Equipment::refreshStatus()`), cridat des de *tots* els camins que el poden alterar. Mai no es duplica la lògica per controller.
9. **Regles d'estat completes abans de codificar:** si una regla d'estat amb més de 2-3 estats és ambigua, cal demanar la llista completa (estat → què el provoca) abans d'escriure codi. No s'han d'endevinar variants.
10. **Les dades de reentrada han de venir completes:** un formulari que torna a obrir dades desades ha de rebre del backend tot el que cal per mostrar-les i editar-les (ids, relacions), no només el valor pla.

## 3. Backend (Laravel)
11. Tota la validació passa per Form Requests, mai dins del controller. Els errors de negoci (422 amb `message`) sí que poden sortir del controller.
12. Associacions amb relacions Eloquent, no consultes en cru.
13. Seguretat: control d'accés verificat al servidor a cada ruta (middleware de rol), contrasenyes sempre xifrades (cast `hashed`), cap credencial al codi.

## 4. Frontend (Vue + Inertia)
14. Tot el text visible passa per `$t('clau')`, amb paritat ca/es (hi ha un test que ho comprova).
15. **Navegació obligatòria:** cada pàgina nova té una manera de sortir-ne (sidebar d'Admin, `OperariNav`, "← Enrere"), des de la primera versió.
16. Els botons d'acció genèrica passen per `Components/Button.vue`. Les excepcions són `ButtonGroup`, `ChartDefects` i `LanguageSelector`.
17. **Bucles asíncrons al frontend:** sempre amb un límit superior validat abans de començar, un progrés visible i un botó de cancel·lar real.
18. **Feedback visual perceptible:** els efectes de hover i de pressió han de tenir un canvi d'un 10% aproximadament, no un canvi mínim.
19. **Mesurar abans de corregir el layout:** si hi ha solapaments o amplades estranyes, cal mesurar el `boundingBox()` a diverses amplades abans de canviar CSS a ull.
20. **Dispositius:** l'app d'Operari prioritza la tauleta (`max-w-5xl`) i l'Admin prioritza l'escriptori (`max-w-7xl`). Les dues han de ser responsive, sense scroll lateral de 375 a 1920px.
