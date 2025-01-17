<?php

declare(strict_types=1);

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
        Schema::table('users', function (Blueprint $table) {
            $table->string('paystack_customer_id')->nullable()->index();
            $table->string('paystack_customer_code')->nullable();
            $table->string('card_type')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex([
                'paystack_customer_id',
            ]);

            $table->dropColumn([
                'paystack_customer_id',
                'paystack_customer_code',
                'card_type',
                'card_last_four',
                'trial_ends_at',
            ]);
        });
    }
};