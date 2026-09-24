<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->foreignUlid('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->index('tenant_id');
            $table->string('avatar_url')->nullable();
            $table->string('status')->default('active');
            $table->unique(['tenant_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['email']);
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'status', 'avatar_url']);
            $table->dropUnique(['tenant_id', 'email']);
        });
    }
};
