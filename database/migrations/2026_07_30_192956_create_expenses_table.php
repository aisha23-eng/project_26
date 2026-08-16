<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('description', 255);
            $table->string('vendor', 255)->nullable();
            $table->date('date');
            $table->string('receipt_path', 255)->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_frequency', 20)->nullable();
            $table->unsignedTinyInteger('recurring_interval')->nullable()->default(1);
            $table->date('recurring_start_date')->nullable();
            $table->date('recurring_end_date')->nullable();
            $table->date('recurring_next_date')->nullable();
            $table->foreignId('parent_expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'category_id']);
            $table->index(['user_id', 'vendor']);
            $table->index(['is_recurring', 'recurring_next_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
