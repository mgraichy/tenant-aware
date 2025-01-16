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
        Schema::create('tenant_switcher', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_name')->unique();
            $table->string('tenant_domain')->unique();
            $table->string('tenant_database')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_switcher');
    }
};
