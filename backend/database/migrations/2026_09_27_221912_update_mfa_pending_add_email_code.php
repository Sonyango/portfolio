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
        Schema::table('mfa_pending', function (Blueprint $table) {
            // totp or email
            $table->string('method')->default('email')->after('token');
            // Hash email code. Null for totp challenges
            $table->string('email_code_hash')->nullable()->after('method');
            // Number of failed verification attempts
            $table->integer('attempts')->default(0)->after('email_code_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mfa_pending', function (Blueprint $table) {
            $table->dropColumn(['method', 'email_code_hash', 'attempts']);
        });
    }
};
