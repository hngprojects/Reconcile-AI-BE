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
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->uuid('plan_id')->nullable()->after('user_id');
            $table->string('stripe_reference')->nullable()->after('plan_id');
            $table->dateTime('start_date')->nullable()->after('stripe_reference');
            $table->dateTime('expire_date')->nullable()->after('start_date');
            $table->boolean('is_active')->default(true);
            $table->integer('reconciliations_used')->default(0)->after('expire_date');

            // Add indexes
            $table->index('user_id');
            $table->index('plan_id');
            $table->index('stripe_reference');
            $table->index('expire_date');
            $table->index('start_date');

            // Add foreign key constraint
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['plan_id']);
            $table->dropIndex(['stripe_reference']);
            $table->dropIndex(['expire_date']);
            $table->dropIndex(['start_date']);
            $table->dropColumn(['plan_id', 'stripe_reference', 'start_date', 'expire_date', 'reconciliations_used']);
        });
    }
};
