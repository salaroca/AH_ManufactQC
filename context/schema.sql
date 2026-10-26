-- Esquema de la BD (SQLite), generat automàticament. NO l'editis a mà.
-- Regenerar: vegeu la skill db-backup / CLAUDE.md (secció Comandes).
-- La font de veritat real són les migracions (database/migrations/).

CREATE TABLE IF NOT EXISTS "migrations" ("id" integer primary key autoincrement not null, "migration" varchar not null, "batch" integer not null);
CREATE TABLE IF NOT EXISTS "users" ("id" integer primary key autoincrement not null, "name" varchar not null, "email" varchar, "username" varchar, "role" varchar not null, "email_verified_at" datetime, "password" varchar not null, "remember_token" varchar, "created_at" datetime, "updated_at" datetime);
CREATE UNIQUE INDEX "users_email_unique" on "users" ("email");
CREATE UNIQUE INDEX "users_username_unique" on "users" ("username");
CREATE TABLE IF NOT EXISTS "password_reset_tokens" ("email" varchar not null, "token" varchar not null, "created_at" datetime, primary key ("email"));
CREATE TABLE IF NOT EXISTS "sessions" ("id" varchar not null, "user_id" integer, "ip_address" varchar, "user_agent" text, "payload" text not null, "last_activity" integer not null, primary key ("id"));
CREATE INDEX "sessions_user_id_index" on "sessions" ("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions" ("last_activity");
CREATE TABLE IF NOT EXISTS "cache" ("key" varchar not null, "value" text not null, "expiration" integer not null, primary key ("key"));
CREATE INDEX "cache_expiration_index" on "cache" ("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks" ("key" varchar not null, "owner" varchar not null, "expiration" integer not null, primary key ("key"));
CREATE INDEX "cache_locks_expiration_index" on "cache_locks" ("expiration");
CREATE TABLE IF NOT EXISTS "jobs" ("id" integer primary key autoincrement not null, "queue" varchar not null, "payload" text not null, "attempts" integer not null, "reserved_at" integer, "available_at" integer not null, "created_at" integer not null);
CREATE INDEX "jobs_queue_index" on "jobs" ("queue");
CREATE TABLE IF NOT EXISTS "job_batches" ("id" varchar not null, "name" varchar not null, "total_jobs" integer not null, "pending_jobs" integer not null, "failed_jobs" integer not null, "failed_job_ids" text not null, "options" text, "cancelled_at" integer, "created_at" integer not null, "finished_at" integer, primary key ("id"));
CREATE TABLE IF NOT EXISTS "failed_jobs" ("id" integer primary key autoincrement not null, "uuid" varchar not null, "connection" varchar not null, "queue" varchar not null, "payload" text not null, "exception" text not null, "failed_at" datetime not null default CURRENT_TIMESTAMP);
CREATE INDEX "failed_jobs_connection_queue_failed_at_index" on "failed_jobs" ("connection", "queue", "failed_at");
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs" ("uuid");
CREATE TABLE IF NOT EXISTS "families" ("id" integer primary key autoincrement not null, "name" varchar not null, "created_at" datetime, "updated_at" datetime);
CREATE UNIQUE INDEX "families_name_unique" on "families" ("name");
CREATE TABLE IF NOT EXISTS "projects" ("id" integer primary key autoincrement not null, "number" varchar not null, "family_id" integer not null, "observations" text, "created_at" datetime, "updated_at" datetime, foreign key("family_id") references "families"("id"));
CREATE UNIQUE INDEX "projects_number_unique" on "projects" ("number");
CREATE TABLE IF NOT EXISTS "order_fabrications" ("id" integer primary key autoincrement not null, "project_id" integer not null, "number" varchar not null, "created_at" datetime, "updated_at" datetime, foreign key("project_id") references "projects"("id") on delete cascade);
CREATE UNIQUE INDEX "order_fabrications_number_unique" on "order_fabrications" ("number");
CREATE TABLE IF NOT EXISTS "sections" ("id" integer primary key autoincrement not null, "name" varchar not null, "description" text, "order" integer not null default '0', "created_at" datetime, "updated_at" datetime);
CREATE TABLE IF NOT EXISTS "equipment" ("id" integer primary key autoincrement not null, "project_id" integer not null, "order_fabrication_id" integer not null, "serie_number" varchar not null, "observations" text, "status" varchar not null default 'pending', "checked_at" datetime, "created_at" datetime, "updated_at" datetime, foreign key("project_id") references "projects"("id") on delete cascade, foreign key("order_fabrication_id") references "order_fabrications"("id") on delete cascade);
CREATE UNIQUE INDEX "equipment_project_id_serie_number_unique" on "equipment" ("project_id", "serie_number");
CREATE TABLE IF NOT EXISTS "answers" ("id" integer primary key autoincrement not null, "equipment_id" integer not null, "question_id" integer not null, "response" varchar not null, "language_chosen" varchar, "created_at" datetime, "updated_at" datetime, foreign key("equipment_id") references "equipment"("id") on delete cascade, foreign key("question_id") references "questions"("id") on delete cascade);
CREATE UNIQUE INDEX "answers_equipment_id_question_id_unique" on "answers" ("equipment_id", "question_id");
CREATE TABLE IF NOT EXISTS "photos" ("id" integer primary key autoincrement not null, "equipment_id" integer not null, "path" varchar not null, "uploaded_at" datetime not null, "created_at" datetime, "updated_at" datetime, foreign key("equipment_id") references "equipment"("id") on delete cascade);
CREATE TABLE IF NOT EXISTS "project_section" ("id" integer primary key autoincrement not null, "project_id" integer not null, "section_id" integer not null, "order" integer not null default '0', "created_at" datetime, "updated_at" datetime, foreign key("project_id") references "projects"("id") on delete cascade, foreign key("section_id") references "sections"("id") on delete cascade);
CREATE UNIQUE INDEX "project_section_project_id_section_id_unique" on "project_section" ("project_id", "section_id");
CREATE TABLE IF NOT EXISTS "defects" ("id" integer primary key autoincrement not null, "equipment_id" integer not null, "answer_id" integer, "tipo" varchar not null, "observation" text, "responsibility" varchar, "actions" text, "created_at" datetime, "updated_at" datetime, "responsible_user_id" integer, foreign key("answer_id") references answers("id") on delete set null on update no action, foreign key("equipment_id") references equipment("id") on delete cascade on update no action, foreign key("responsible_user_id") references "users"("id") on delete set null);
CREATE TABLE IF NOT EXISTS "question_categories" ("id" integer primary key autoincrement not null, "name" varchar not null, "order" integer not null default '0', "created_at" datetime, "updated_at" datetime);
CREATE UNIQUE INDEX "question_categories_name_unique" on "question_categories" ("name");
CREATE TABLE IF NOT EXISTS "question_templates" ("id" integer primary key autoincrement not null, "text" varchar not null, "question_category_id" integer not null, "is_required" tinyint(1) not null default '1', "created_at" datetime, "updated_at" datetime, foreign key("question_category_id") references "question_categories"("id") on delete restrict);
CREATE TABLE IF NOT EXISTS "questions" ("id" integer primary key autoincrement not null, "section_id" integer not null, "text" varchar not null, "order" integer not null default ('0'), "is_required" tinyint(1) not null default ('1'), "created_at" datetime, "updated_at" datetime, "question_category_id" integer not null, "question_template_id" integer, foreign key("section_id") references sections("id") on delete cascade on update no action, foreign key("question_category_id") references question_categories("id") on delete restrict on update no action, foreign key("question_template_id") references "question_templates"("id") on delete set null);
