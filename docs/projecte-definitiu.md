# AH ManufactQC: del prototip al projecte definitiu

*Document de context per planificar el projecte definitiu. Estat a 23/09/2026.*

---

## 0. Com s'ha de fer servir aquest document

Aquest document resumeix **tot el que s'ha construït i decidit en el prototip (demo) d'AH ManufactQC**, perquè serveixi de punt de partida per planificar el projecte definitiu **abans de tocar codi**.

Si ets Claude i llegeixes això com a coneixement d'un Projecte de claude.ai:
- **No tens accés al codi.** Tot el que cal saber del prototip és aquí. Si alguna cosa no hi surt, pregunta-la en lloc de suposar-la.
- L'objectiu d'aquest Projecte és **dissenyar el sistema definitiu**: arquitectura, model de dades, integracions i fases. No és implementar.
- Les **preguntes obertes** (secció 12) són el que més condiciona el disseny. Prioritza aclarir-les amb l'usuari.
- Respon sempre en **català**.
- La implementació es farà després amb Claude Code sobre el repositori, on hi ha un `CLAUDE.md` amb el detall tècnic complet.

---

## 1. Context i objectiu

**AH ManufactQC** és un sistema digital de **control de qualitat per a inspeccions de fabricació**. Substitueix el control en paper: l'operari de qualitat inspecciona cada equip fabricat (per número de sèrie), respon un qüestionari (Sí / No / Defecte), registra els defectes trobats (i qui n'és responsable) i finalitza la inspecció. Responsables de qualitat i administradors gestionen els qüestionaris i consulten estadístiques.

El prototip s'ha fet per **consolidar idees i tenir una demo que es pugui ensenyar**. El projecte definitiu probablement tindrà canvis importants:
- **Bootstrap** en lloc de Tailwind CSS;
- una **API** pensada per ser consumida per altres sistemes;
- **connexió amb la base de dades o el sistema de l'empresa (ERP)**;
- possiblement **altres departaments** (Producció, Embalatge, Magatzem…) sobre un mateix nucli.

---

## 2. Què fa el prototip avui

### 2.1 Rols d'usuari

| Rol | Accés | Com entra |
|---|---|---|
| **Administrador** | Backend web (gestió completa) i també l'àrea de Qualitat | Email + contrasenya |
| **Responsable QC** | El mateix que Administrador (ara mateix no hi ha cap diferència de permisos) | Email + contrasenya |
| **Operari de Qualitat** | Només l'àrea de Qualitat (tauleta) | Usuari + contrasenya |
| **Operari de Producció** | No entra a l'app. Existeix per poder-lo assignar com a **responsable d'un defecte** | — |

La pantalla d'inici té dos botons: "Accés Qualitat" (operaris) i "Admin / QC". Des del backend, Admin/QC poden saltar a l'àrea de Qualitat sense tornar a iniciar sessió.

### 2.2 Flux de l'operari de qualitat (tauleta)

1. **Cerca d'OF.** Llistat de totes les ordres de fabricació, amb un cercador que el filtra. Cada OF mostra el projecte, la família, la descripció, el nombre d'equips i el seu estat: *Per començar*, *Falten N equips per revisar*, *Acabada* o *Sense equips*. Les que tenen feina pendent surten primer.
2. **Llistat d'equips de l'OF.** Números de sèrie amb l'estat de cadascun, en colors (vegeu 4.1), i el nombre de defectes.
3. **Formulari d'inspecció d'un equip.** Preguntes agrupades per *part de descripció* i, dins de cada part, per *categoria*. Cada pregunta es respon amb **Sí / No / Defecte**. Tornar a clicar la resposta ja marcada la desmarca.
   - Si es marca **Defecte**, s'obre un pop-up per registrar-lo: tipus (visual / dimensional / funcional), observació, responsabilitat (producció / proveïdor / disseny), **operari de producció responsable** (obligatori si la responsabilitat és producció) i accions preses.
   - Una pregunta pot tenir **diversos defectes**. Els defectes es poden **editar i eliminar**, i es conserven com a historial encara que després la resposta canviï a Sí.
   - Hi ha observacions de text lliure per a l'equip (opcional).
4. **Finalitzar.** Pop-up de fotos (fins a 6, opcionals, de 8 MB com a màxim cadascuna). En finalitzar es registra la data i l'hora de la inspecció (`checked_at`) i es torna al llistat d'equips.
5. En qualsevol moment l'operari pot **sortir sense acabar**, i l'equip queda en el seu estat intermedi.

### 2.3 Backend Admin/QC (escriptori)

El menú lateral està organitzat per blocs:

- **Dashboard:** indicadors (equips totals, equips comprovats, % completat, defectes totals), gràfics de defectes per tipus, per responsabilitat i **% d'equips amb defecte per part de descripció**, i últimes fotos. Es pot filtrar per projecte i dates, i cada gràfic es pot veure com a taula.
- **Producció**
  - **Projectes:** número, família (catàleg ampliable) i descripció, formada per una llista **ordenada** de parts de descripció.
  - **Ordres de fabricació:** cada OF pertany a un projecte. Des de cada OF es gestionen els seus equips, amb un **generador de números de sèrie consecutius** (màxim 500 per tanda, amb progrés visible i un botó per aturar-lo).
- **Qüestionaris**
  - **Parts de descripció:** cada part té les seves preguntes, que s'ordenen arrossegant-les.
  - **Banc de preguntes:** preguntes reutilitzables que es **copien** a les parts (vegeu 4.4).
  - **Categories de preguntes:** catàleg ordenable (inicialment Estètica, Funcional / Mecànica, Electrònica).
- **Administració**
  - **Usuaris:** un sol formulari per als 4 rols.
- Selector d'idioma (català / castellà), enllaç a l'àrea de Qualitat i botó de tancar sessió.

---

## 3. Model de dades del prototip

```
Family 1──N Project N──M Section (part de descripció)  [ordre dins el projecte]
                │              │
                │              1──N Question N──1 QuestionCategory
                │                       │  └──N──1 QuestionTemplate (banc, opcional)
                1──N OrderFabrication   │
                │          │            │
                └──1──N Equipment N──1──┘ (via OF)
                           │
                           1──N Answer N──1 Question
                           1──N Defect  (opcionalment lligat a una Answer; responsable = User)
                           1──N Photo
User (rol: admin / qc / operari / operari_produccio)
```

| Entitat | Camps principals | Regles |
|---|---|---|
| **Family** | nom | Nom únic. Un projecte té exactament una família. No es pot eliminar si algun projecte la fa servir |
| **Project** | número (p. ex. `1400C0000.00`), família, observacions | Número únic |
| **Section** (*part de descripció*) | nom (p. ex. `AH17DX2`, `TS`, `USB`, `QUALITAT`), detall | Els projectes en trien un subconjunt **ordenat**. L'ordre forma la descripció, p. ex. "AH17DX2 TS USB" |
| **QuestionCategory** | nom, ordre | Nom únic. No es pot eliminar si hi ha preguntes o plantilles que la fan servir |
| **Question** | part, text, categoria, ordre, obligatòria, plantilla d'origen | Pertany a una sola part |
| **QuestionTemplate** (*banc*) | text, categoria, obligatòria | Es copia a les parts, no s'hi enllaça |
| **OrderFabrication** (OF) | número (p. ex. `2026/01/0000123`), projecte | **Número únic a tota l'aplicació** |
| **Equipment** | número de sèrie, projecte, OF, observacions, estat, `checked_at` | Número de sèrie únic **per projecte**, no globalment. L'estat el calcula el sistema |
| **Answer** | equip, pregunta, resposta (`yes` / `no` / `defect`), idioma | Una sola resposta per equip i pregunta. Desmarcar = esborrar-la |
| **Defect** | equip, resposta (opcional), tipus, observació, responsabilitat, operari responsable, accions | Diversos per pregunta. Si s'esborra la resposta, el defecte es manté |
| **Photo** | equip, ruta del fitxer, data | Màxim 6 per equip. Es guarden en un disc privat del servidor |
| **User** | nom, email (Admin/QC) **o** usuari (Operaris), rol, contrasenya | |

---

## 4. Regles de negoci consolidades

Són el resultat de diverses iteracions amb l'usuari i s'han de **conservar** al projecte definitiu.

### 4.1 Estat d'un equip (5 estats)

L'estat es recalcula automàticament després de **qualsevol** canvi en respostes o defectes, sempre des d'una única funció central.

**Mentre no està finalitzat**, l'estat es calcula en viu segons les respostes actuals:

| Estat | Color | Condició |
|---|---|---|
| **Pendent amb defectes** | vermell | Alguna resposta actual és "Defecte" (té prioritat sobre la resta) |
| **Falten respostes** | ambre | Ja té alguna resposta, però no totes les obligatòries |
| **Pendent** | gris | Encara no té cap resposta |

**Un cop finalitzat**, l'estat depèn de l'**historial**:

| Estat | Color | Condició |
|---|---|---|
| **Correcte amb defectes** | verd maragda | En algun moment de la inspecció es va registrar un defecte, encara que després es corregís |
| **Correcte** | verd | No s'ha registrat mai cap defecte |

Aquesta asimetria (en viu mentre es revisa, historial un cop acabat) és intencionada: va costar tres iteracions arribar-hi. Només **eliminar** un defecte (perquè es va registrar per error) fa baixar un equip finalitzat a "Correcte". Canviar la resposta no n'hi ha prou.

### 4.2 Finalitzar una inspecció

- Cal que **totes les preguntes obligatòries** tinguin resposta.
- **No es pot finalitzar si alguna pregunta està marcada com a "Defecte"**, encara que el defecte ja estigui documentat. El procediment és: marcar Defecte → documentar-lo → canviar la resposta a Sí o No (el defecte queda com a historial) → finalitzar.
- Si un equip ja finalitzat torna a tenir una pregunta sense resposta o marcada com a Defecte, **es reobre automàticament** (s'esborra `checked_at`).
- El botó de finalitzar queda bloquejat mentre s'estigui desant alguna resposta, per evitar errors per timing.

### 4.3 Parts de descripció

- Una *part de descripció* és alhora **una paraula de la descripció del projecte** i **la propietària d'un qüestionari**.
- Triar la descripció d'un projecte **és** triar quins qüestionaris s'hi apliquen.
- L'ordre de les parts importa: "AH17DV2 TS USB" és una seqüència concreta, no alfabètica.
- No totes les parts necessiten totes les categories: "el 90% sí, però algun no té part electrònica". La categoria simplement no hi apareix si no té preguntes.

### 4.4 Banc de preguntes (decisió: còpia, no enllaç)

- En afegir preguntes del banc a una part, se'n **copien**: text, categoria i obligatòria.
- Editar la pregunta al banc **no** canvia les còpies ja fetes. Eliminar-la del banc tampoc les esborra.
- **Motiu:** preservar el text exacte de les inspeccions ja fetes i no complicar el model de respostes.
- Cada còpia recorda de quina plantilla ve. Si mai cal "aplicar un canvi a totes les parts", es pot fer sense migrar dades.
- Una pregunta que ja existeix en una part es pot enviar al banc amb la casella "Desa-la també al banc".

### 4.5 Altres regles

- Les OF i els equips els creen Admin o QC, **no** els operaris.
- L'estat i `checked_at` d'un equip **mai** es poden modificar a mà, només a través del flux d'inspecció.
- Les observacions de text lliure no influeixen en l'estat.
- Els números de sèrie solen tenir 6 xifres, però **no s'ha de fixar la longitud**. El generador en sèrie respecta el format que s'hi escriu, amb el prefix i els zeros a l'esquerra.

---

## 5. Stack i arquitectura actual del prototip

| Capa | Tecnologia |
|---|---|
| Backend | Laravel 13 (PHP), Inertia.js v3 |
| Frontend | Vue 3 + Vite, **Tailwind CSS v3**, icones `@lucide/vue`, arrossegar amb `vue-draggable-plus` |
| Base de dades | **SQLite** (un fitxer) |
| Autenticació | Sessions de Laravel (cookie), un sol *guard* per a tots els rols |
| Idiomes | vue-i18n: català + castellà per a tots els textos de la interfície |
| Fitxers | Disc local privat (`storage/app/photos`) |
| Tests | 166 tests de backend (Pest) + 10 de frontend (Vitest), tots en verd |

**Arquitectura:** és una sola aplicació Laravel. El frontend Vue no llegeix mai la base de dades directament, sinó que crida una **API JSON interna**:
- `/api/*` per a Admin/QC: CRUD de projectes, famílies, parts, preguntes, categories, banc, OF, equips, usuaris, més el dashboard i les fotos.
- `/operari/api/*` per a Qualitat: cerca d'OF, equips, respostes, defectes, fotos i operaris de producció.

Aquesta API funciona amb la sessió del navegador (no amb tokens) i retorna directament els models de la base de dades, sense una capa de format estable. **Ja és mig camí cap a una arquitectura "API-first"**, però no està preparada perquè la consumeixin sistemes externs.

---

## 6. Decisions d'experiència d'usuari consolidades

- **L'àrea de Qualitat està pensada per a tauleta.** Les pantalles ocupen fins a 1024 px d'amplada (87% en un iPad horitzontal) i també funcionen al mòbil. El llistat d'OF té scroll propi, i el buscador sempre és visible.
- **El backend està pensat per a escriptori**, però és responsive: continguts fins a 1280 px, i les taules tenen scroll horitzontal propi a les pantalles estretes.
- **Navegació:** totes les pàgines tenen una manera de tornar enrere i de sortir. Admin té un menú lateral agrupat per blocs.
- **Botons:** un sol component amb 5 variants (principal, perill, contorn, text, text-perill) i efecte visible en prémer i en passar-hi el ratolí.
- **Terminologia:** a la interfície es diu **"Part de descripció"**, no "secció". El camp de text lliure de cada part es diu **"Detall"**.
- **Colors d'estat:** gris (pendent), ambre (falten respostes), vermell (pendent amb defectes), verd (correcte), verd maragda (correcte amb defectes). "Correcte amb defectes" ha de ser **verd**, no taronja, perquè la inspecció ha acabat correctament.
- **Accions llargues des del navegador** (p. ex. crear 500 equips): sempre amb un límit, un indicador de progrés i un botó per aturar-les.

---

## 7. Lliçons apreses (per no repetir errors)

1. **Estat derivat en un sol lloc.** L'estat de l'equip es calculava en diversos llocs i quedava desfasat. Ara hi ha una única funció, cridada des de totes les accions que el poden alterar.
2. **Quan es reobre un formulari, cal carregar totes les dades**: identificadors i relacions (defectes, responsable), no només el valor que es veu. Si no, es perd informació en tornar a entrar.
3. **Si l'usuari rebutja dues vegades una regla, cal demanar-li l'especificació completa** en lloc de provar una tercera variant (va passar amb els estats).
4. **Sempre una còpia de seguretat de la base de dades** abans de canvis que puguin afectar dades reals.
5. **Els solapaments visuals s'han de mesurar**, no suposar que són de capes (z-index).
6. **La navegació és un requisit**, no un acabat: cada pàgina nova ha de tenir una manera de sortir-ne.

---

## 8. Limitacions i deute tècnic del prototip

Coses acceptables per a una demo que el projecte definitiu hauria de resoldre:

| Tema | Situació actual | Impacte |
|---|---|---|
| **Traçabilitat** | **No es guarda quin operari ha respost cada pregunta, qui ha registrat cada defecte ni qui ha finalitzat l'equip.** Només es guarda la data de finalització | Alt en control de qualitat: normalment cal saber qui ha inspeccionat què |
| **Auditoria** | No hi ha historial de canvis (qui ha modificat una pregunta, un projecte…) | Mitjà-alt |
| **Permisos** | Admin i QC tenen exactament els mateixos permisos. No hi ha permisos per departament | Mitjà |
| **Base de dades** | SQLite, i un historial de migracions incremental amb canvis de dades | Cal un motor de servidor i migracions netes |
| **API** | Sense tokens, sense versions, retorna els models tal qual, sense documentació | No es pot obrir a altres sistemes tal com està |
| **Catàlegs fixos al codi** | Els tipus de defecte (visual / dimensional / funcional) i les responsabilitats (producció / proveïdor / disseny) són fixos | Probablement haurien de ser catàlegs editables |
| **Traduccions** | Els missatges d'error de validació surten en anglès. Els noms de catàleg (parts, categories, famílies) no es tradueixen | Mitjà |
| **Llistats** | Sense paginació. El llistat d'OF de l'operari està limitat a 50 | Problema a mesura que creixin les dades |
| **Nomenclatura interna** | El codi diu `Section`, i la interfície "part de descripció" | Confusió en llegir el codi. Al definitiu, fer servir un sol nom |
| **Fotos** | Disc local del servidor | Cal decidir on es guarden i quina còpia de seguretat se'n fa |
| **Operativa** | Sense desplegament a servidor, sense còpies de seguretat automàtiques, sense revisió de seguretat | Imprescindible per a producció |
| **Sense connexió** | Cal connexió permanent | Depèn de la cobertura wifi a planta |
| **Dades mestres** | Projectes, OF i equips es creen a mà | Probablement haurien de venir de l'ERP |

---

## 9. Canvis previstos per al projecte definitiu

### 9.1 Bootstrap en lloc de Tailwind
- Afecta **només l'aspecte**: les classes CSS de les ~25 pàgines i components. No afecta la lògica, l'API, les traduccions ni les regles.
- **Decisió pendent:** fer servir només el CSS de Bootstrap i deixar el comportament a Vue, o bé la llibreria **BootstrapVueNext**. No convé barrejar el JavaScript de Bootstrap amb Vue.
- **Pregunta:** és un estàndard de l'empresa?

### 9.2 API per a altres sistemes
- Autenticació per **token** (Laravel Sanctum) per a sistemes externs, mantenint la sessió per a les pantalles pròpies.
- **Versions** (`/api/v1/...`).
- **Format de resposta estable** (API Resources), independent de l'estructura de la base de dades.
- Documentació **OpenAPI/Swagger** i límits de peticions.
- **Pregunta:** qui la consumirà?

### 9.3 Connexió amb el sistema de l'empresa (ERP)

| Opció | Com funciona | A favor | En contra |
|---|---|---|---|
| **A) Lectura directa** | L'app consulta la base de dades de l'ERP en temps real | Dades sempre al dia | Si l'ERP cau o va lent, l'app també. Queda lligada a les taules de l'ERP |
| **B) Importació periòdica** (la més habitual) | Un procés copia cada X minuts OF, projectes i equips a la base de dades pròpia | Funciona encara que l'ERP caigui. Es controla què entra | Uns minuts de retard |
| **C) API de l'ERP** | Es fa servir una API que ofereixi l'ERP | Separació neta | Depèn que l'ERP en tingui |

Cal decidir **quines dades són "mestres" a l'ERP** (probablement projectes, OF i números de sèrie) i quines són pròpies de l'app (qüestionaris, inspeccions, defectes). També cal decidir **si el resultat de la inspecció s'ha d'enviar a l'ERP**.

---

## 10. Arquitectura proposada: nucli + mòduls per departament

```
          Producció     Qualitat     Embalatge     Magatzem      ERP / altres
         (tauleta)   (tauleta+web)   (tauleta)    (PDA/web)       sistemes
              │            │             │             │               │
              └────────────┴──────┬──────┴─────────────┴───────────────┘
                                  │  crides a l'API (JSON + token)
                        ┌─────────▼──────────┐
                        │     API central    │  ← el "motor"
                        │ usuaris i permisos │
                        │ projectes · OF ·   │
                        │ equips (nº sèrie)  │
                        ├────────────────────┤
                        │ mòdul Producció    │
                        │ mòdul Qualitat     │  ← el que ja existeix al prototip
                        │ mòdul Embalatge    │
                        │ mòdul Magatzem     │
                        └─────────┬──────────┘
                                  │
                          Base de dades  ⇄  ERP (importació / consulta)
```

- **Les aplicacions de cada departament són independents** (pantalles, usuaris i desplegament), però **les dades principals són compartides**. Un mateix equip passa per Producció → Qualitat → Embalatge → Magatzem.
- **El nucli comú** conté: usuaris, rols i permisos, departaments, projectes, famílies, OF, equips i la seva traçabilitat entre departaments.
- **Cada mòdul** hi afegeix el seu domini. Qualitat: qüestionaris, inspeccions, defectes i fotos.
- **Recomanació: un monòlit modular** (una sola aplicació Laravel organitzada per mòduls), no microserveis. És més simple de desplegar, té una sola base de dades i és adequat per a un equip petit o mitjà. Els microserveis només compensen amb molts equips de desenvolupament.
- Els **catàlegs de departaments i d'habilitats de l'operari** (pot soldar, ajustar…), que es van ajornar al prototip, encaixen en el nucli comú.

---

## 11. Què s'aprofita del prototip

| Element | Aprofitament | Nota |
|---|---|---|
| **Regles de negoci** (secció 4) | Total | És el més valuós: van costar moltes iteracions |
| **Model de dades** (secció 3) | Alt | Revisar-ne els noms i afegir traçabilitat i departaments |
| **Decisions d'UX** (secció 6) | Alt | Independents de Tailwind o Bootstrap |
| **Tests** (166 de backend) | Alt | Descriuen el comportament esperat. Serveixen com a llista de comprovació encara que es reescrigui el codi |
| **Codi backend** (Laravel) | Alt si es manté Laravel | Models, validacions i controladors |
| **Codi frontend** (Vue) | Mitjà | Es manté la lògica de les pantalles; cal refer les classes si es passa a Bootstrap |
| **Traduccions** (ca / es) | Alt | |
| **Migracions de la base de dades** | Baix | Millor començar amb migracions netes |
| **Dades** | Només les reals que valgui la pena conservar | Moltes són de prova |

**Recomanació:** si el definitiu continua amb Laravel + Vue, és millor **fer evolucionar el repositori actual** (endreçar-lo, canviar de base de dades, afegir API, mòduls i ERP) que començar de zero. Començar de zero vol dir tornar a trobar problemes que ja s'han resolt. Té sentit començar de nou si canvia l'stack, si l'ERP obliga a una arquitectura diferent o si ho exigeix l'empresa.

---

## 12. Preguntes obertes (a resoldre abans de dissenyar)

**ERP i dades**
1. Quin ERP o sistema té l'empresa, i amb quin motor de base de dades (SQL Server, Oracle, MySQL…)?
2. Hi haurà accés de només lectura a la seva base de dades, o l'ERP té API?
3. Quines dades vénen de l'ERP (projectes, OF, números de sèrie, famílies, usuaris?) i quines són pròpies de l'app?
4. S'ha d'enviar alguna cosa cap a l'ERP (resultat de la inspecció, defectes)?
5. Amb quina freqüència canvien les OF? Quin retard és acceptable?

**Departaments i abast**
6. Quins departaments entren a la primera versió i quins més endavant?
7. Què fa cada departament amb un equip? Quin és el recorregut real d'un equip per planta?
8. Cal saber en quin departament o fase és cada equip en tot moment?

**Usuaris, permisos i traçabilitat**
9. Cal registrar quin operari respon cada pregunta, registra cada defecte i finalitza cada equip? (Recomanat.)
10. Quines diferències de permisos hi ha d'haver entre Admin, Responsable QC i els responsables d'altres departaments?
11. Els usuaris vénen d'un directori de l'empresa (Active Directory, etc.) o es gestionen dins l'app?

**Infraestructura**
12. On s'allotjarà: servidor propi de l'empresa o núvol? Quin motor de base de dades es fa servir a l'empresa?
13. Hi ha wifi a totes les zones on s'usaran les tauletes, o cal que funcioni sense connexió?
14. Quines tauletes o dispositius es faran servir?
15. Quina política de còpies de seguretat i de conservació de dades i fotos cal?

**Interfície**
16. Bootstrap és un requisit de l'empresa? Hi ha una guia d'estil corporativa?
17. Cal traduir també els noms dels catàlegs (parts, categories…) o n'hi ha prou amb la interfície?
18. Qui consumirà l'API a part de l'app pròpia?

---

## 13. Proposta de fases (orientativa, pendent de les respostes)

| Fase | Contingut |
|---|---|
| **0. Definició** | Resoldre les preguntes obertes. Recorregut real d'un equip per planta. Accés i estructura de l'ERP. Decisió d'stack (Bootstrap sí o no) |
| **1. Fonaments** | Base de dades de servidor, migracions netes, nomenclatura definitiva, nucli comú (usuaris, rols, permisos, departaments, projectes, OF, equips), traçabilitat (qui i quan), auditoria bàsica |
| **2. Integració amb l'ERP** | Importació (o consulta) de projectes, OF i números de sèrie. Decidir què continua sent editable a mà |
| **3. Mòdul Qualitat** | Portar el prototip al nou nucli conservant les regles de la secció 4. Catàlegs editables de tipus de defecte i responsabilitats. Frontend amb l'estil definitiu |
| **4. API externa** | Tokens, versions, format estable, documentació |
| **5. Posada en producció** | Desplegament, còpies de seguretat automàtiques, revisió de seguretat, formació, proves a planta amb tauletes reals |
| **6+. Altres departaments** | Producció, Embalatge, Magatzem… com a mòduls sobre el mateix nucli |

---

## 14. Glossari

| Terme (interfície) | Nom al codi del prototip | Significat |
|---|---|---|
| Projecte | `Project` | Producte fabricat, identificat per un número (p. ex. `1400C0000.00`) |
| Família | `Family` | Agrupació de projectes (p. ex. DB2, DB3, D2 UC) |
| Part de descripció | `Section` | Paraula de la descripció d'un projecte (p. ex. AH17DX2) que porta el seu qüestionari |
| Descripció | *(calculada)* | Les parts d'un projecte en ordre, p. ex. "AH17DX2 TS USB" |
| Pregunta | `Question` | Pregunta Sí / No / Defecte d'una part |
| Categoria | `QuestionCategory` | Agrupació de preguntes (Estètica, Funcional / Mecànica, Electrònica…) |
| Banc de preguntes | `QuestionTemplate` | Preguntes reutilitzables que es copien a les parts |
| OF | `OrderFabrication` | Ordre de fabricació (p. ex. `2026/01/0000123`), agrupa equips d'un projecte |
| Equip | `Equipment` | Unitat fabricada, identificada pel número de sèrie |
| Resposta | `Answer` | Resposta d'un equip a una pregunta |
| Defecte | `Defect` | Problema trobat: tipus, observació, responsabilitat, responsable i accions |
| Finalitzar | `checked_at` | Tancar la inspecció d'un equip |
