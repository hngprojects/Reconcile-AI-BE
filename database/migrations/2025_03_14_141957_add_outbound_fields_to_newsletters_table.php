<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_letters', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('subscribed');
            $table->string('business_name')->nullable()->after('full_name');
            $table->string('phone_number')->nullable()->after('business_name');
        });
    }

    public function down(): void
    {
        Schema::table('news_letters', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'business_name', 'phone_number']);
        });
    }
};
