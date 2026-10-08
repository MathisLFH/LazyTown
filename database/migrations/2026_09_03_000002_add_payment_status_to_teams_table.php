<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->after('is_personal');
            $table->string('payment_reference')->nullable()->after('payment_status');
            $table->timestamp('payment_paid_at')->nullable()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_reference', 'payment_paid_at']);
        });
    }
};
