<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('discount_type')->index();
            $table->unsignedInteger('discount_value')->default(0);
            $table->unsignedInteger('max_discount_amount')->nullable();
            $table->unsignedInteger('min_order_amount')->default(0);
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->unsignedInteger('usage_limit_total')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_public')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->index(['is_active', 'is_public', 'sort_order']);
            $table->index(['branch_id', 'is_active']);
        });

        Schema::create('voucher_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['voucher_id', 'locale']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('voucher_id')->nullable()->after('discount_total')->constrained()->nullOnDelete();
            $table->string('voucher_code')->nullable()->after('voucher_id');
            $table->json('voucher_snapshot')->nullable()->after('voucher_code');
        });

        Schema::create('voucher_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('customer_key')->nullable()->index();
            $table->unsignedInteger('discount_total')->default(0);
            $table->timestamps();

            $table->unique('order_id');
            $table->index(['voucher_id', 'customer_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_redemptions');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn(['voucher_code', 'voucher_snapshot']);
        });

        Schema::dropIfExists('voucher_translations');
        Schema::dropIfExists('vouchers');
    }
};
