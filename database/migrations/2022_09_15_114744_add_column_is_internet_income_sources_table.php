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
        Schema::table('income_sources', function (Blueprint $table) {
            $table->boolean('is_internet')->default(false)->comment('Услуги интернета')->after('is_parking');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('income_sources', function (Blueprint $table) {
            $table->dropColumn('is_internet');
        });
    }
};
