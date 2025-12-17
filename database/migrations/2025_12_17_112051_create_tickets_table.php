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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 6, 2);
            $table->decimal('iva_amount', 6, 2);
            $table->decimal('total_amount', 10, 2);
            $table->string('uri')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('needs_revision')->default(false);
            $table->boolean('finalized_by_admin')->default(false);
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('supervisor_id')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('category_id')
                ->references('id')->on('ticket_categories')
                ->restrictOnDelete();

            $table->foreign('reviewed_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
