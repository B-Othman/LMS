<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // GIN index for course full-text search
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_courses_fts ON courses
            USING GIN (
                to_tsvector('english',
                    coalesce(title, '') || ' ' ||
                    coalesce(description, '') || ' ' ||
                    coalesce(short_description, '')
                )
            )
        ");

        // GIN index for user full-text search
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_users_fts ON users
            USING GIN (
                to_tsvector('english',
                    coalesce(first_name, '') || ' ' ||
                    coalesce(last_name, '') || ' ' ||
                    coalesce(email, '')
                )
            )
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_courses_fts');
        DB::statement('DROP INDEX IF EXISTS idx_users_fts');
    }
};
