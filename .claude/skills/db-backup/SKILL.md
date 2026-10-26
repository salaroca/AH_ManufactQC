---
name: db-backup
description: Fa una còpia de seguretat de database/database.sqlite (còpia binària + volcat .sql) abans de qualsevol canvi que pugui invalidar dades reals (enums, columnes, migrate:fresh). Constitució §7.
---

# Còpia de seguretat de la BD

La BD conté dades reals introduïdes per l'usuari. Abans de canviar un enum, eliminar una columna o un valor, o executar qualsevol `migrate:fresh` o `migrate:rollback`:

```bash
TS=$(date +%Y%m%d-%H%M%S)
cp database/database.sqlite "database/database.sqlite.backup-$TS"
sqlite3 database/database.sqlite .dump > "database/database.sqlite.backup-$TS.sql"
ls -la database/*.backup-$TS*
```

Totes dues còpies queden ignorades per `database/.gitignore`. A més:
1. **Comprova si el canvi realment afecta files existents.** Per exemple, `SELECT count(*) FROM equipment WHERE status = 'valor_antic';`. Sovint n'hi ha 0 i no cal cap migració de dades.
2. Si n'hi ha, explica-ho a l'usuari **abans** de tocar res, i proposa com migrar-les.
3. Després de la migració, regenera `context/schema.sql` (vegeu `CLAUDE.md` → Comandes).
4. Digues a l'usuari el nom de les còpies creades.

**Mai** no facis `migrate:fresh --seed` sobre la BD real sense permís explícit: per als tests ja hi ha la BD en memòria de Pest.
