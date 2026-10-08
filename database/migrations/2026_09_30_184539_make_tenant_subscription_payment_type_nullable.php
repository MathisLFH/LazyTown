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
        Schema::table('tenant_subscriptions', function (Blueprint $table): void {
            $table->string('payment_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tenant_subscriptions')
            ->whereNull('payment_type')
            ->update(['payment_type' => '']);

        Schema::table('tenant_subscriptions', function (Blueprint $table): void {
            $table->string('payment_type')->nullable(false)->change();
        });
    }
};
