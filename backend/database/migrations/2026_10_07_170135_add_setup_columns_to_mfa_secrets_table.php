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
        Schema::table('mfa_secrets', function (Blueprint $table) {
            // Separate pending setup from active enrollments.
            $table->string('pending_secret')->nullable()->after('secret');
            $table->timestamp('setup_started_at')->nullable()->after('pending_secret');
            // Store recovery codes hashed
            $table->json('recovery_codes_hashed')->nullable()->after('recovery_codes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mfa_secrets', function (Blueprint $table) {
            $table->dropColumn([
                'pending_secret',
                'setup_started_at',
                'recovery_codes_hashed',
            ]);
        });
    }
};
