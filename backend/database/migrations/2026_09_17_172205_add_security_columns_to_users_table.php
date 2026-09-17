<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('locked_until')->nullable()->after('remember_token');
            $table->integer('failed_attempts')->default(0)->after('locked_until');
            $table->timestamp('last_login_at')->nullable()->after('failed_attempts');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->timestamp('password_changed_at')->nullable()->after('last_login_ip');
            $table->boolean('mfa_enabled')->default(false)->after('password_changed_at');
            $table->timestamp('last_activity_at')->nullable()->after('mfa_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'locked_until', 'failed_attempts', 'last_login_at',
                'last_login_ip', 'password_changed_at', 'mfa_enabled', 'last_activity_at'
            ]);
        });
    }
};
