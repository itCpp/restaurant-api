<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('overdue_exceptions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('source_id')->nullable()->index();
            $table->bigInteger('purpose_id')->nullable();
            $table->string('month', 7)->nullable();
            $table->timestamps();

            $table->index(['source_id', 'purpose_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('overdue_exceptions');
    }
};
