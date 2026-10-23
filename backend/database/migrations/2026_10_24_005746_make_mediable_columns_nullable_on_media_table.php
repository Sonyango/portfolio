<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('mediable_type')->nullable()->change();
            $table->unsignedBigInteger('mediable_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('media')
            ->whereNull('mediable_type')
            ->orWhereNull('mediable_id')
            ->exists()) {
            throw new RuntimeException(
                'Cannot roll back the media relationship migration while media records have no associated model.'
            );
        }

        Schema::table('media', function (Blueprint $table) {
            $table->string('mediable_type')->nullable(false)->change();
            $table->unsignedBigInteger('mediable_id')->nullable(false)->change();
        });
    }
};
