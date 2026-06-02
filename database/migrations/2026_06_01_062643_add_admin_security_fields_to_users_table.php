<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('admin_pin_hash')->nullable()->after('is_primary_admin');
            $table->timestamp('admin_verified_once_at')->nullable()->after('admin_pin_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['admin_pin_hash', 'admin_verified_once_at']);
        });
    }
};