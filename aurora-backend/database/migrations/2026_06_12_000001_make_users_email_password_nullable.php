<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            DB::statement('
                CREATE TABLE users_new (
                    id integer primary key autoincrement not null,
                    name varchar not null,
                    email varchar null,
                    email_verified_at datetime null,
                    password varchar null,
                    remember_token varchar(100) null,
                    created_at datetime null,
                    updated_at datetime null
                )
            ');

            DB::statement('
                INSERT INTO users_new (
                    id,
                    name,
                    email,
                    email_verified_at,
                    password,
                    remember_token,
                    created_at,
                    updated_at
                )
                SELECT
                    id,
                    name,
                    email,
                    email_verified_at,
                    password,
                    remember_token,
                    created_at,
                    updated_at
                FROM users
            ');

            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_new RENAME TO users');
            DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');

            Schema::enableForeignKeyConstraints();

            return;
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
            DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NULL');

            return;
        }

        DB::statement('ALTER TABLE users ALTER COLUMN email DROP NOT NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN password DROP NOT NULL');
    }

    public function down(): void
    {
        // MVP Telegram users are intentionally allowed to exist without email/password.
    }
};
