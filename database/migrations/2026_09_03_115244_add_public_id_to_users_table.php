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
        Schema::table('users', function (Blueprint $table) {
            $table->string('public_id', 8)->nullable()->unique()->after('id');
        });

        DB::table('users')->select('id')->orderBy('id')->eachById(function (object $user): void {
            do {
                $publicId = (string) random_int(10000000, 99999999);
            } while (DB::table('users')->where('public_id', $publicId)->exists());

            DB::table('users')->where('id', $user->id)->update(['public_id' => $publicId]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
