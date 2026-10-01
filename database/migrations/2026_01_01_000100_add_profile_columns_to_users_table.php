<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role')->default('surveyor')->index()->after('email');
            $table->string('phone', 30)->nullable()->after('role');
            $table->string('organization')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->index()->after('organization');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'username', 'role', 'phone', 'organization', 'is_active', 'last_login_at',
            ]);
        });
    }
};
