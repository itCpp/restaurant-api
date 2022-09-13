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
        Schema::table('income_parts', function (Blueprint $table) {
            $table->bigInteger('building_id')->nullable()->comment('Идентификатор здания')->after('id')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('income_parts', function (Blueprint $table) {
            $table->dropColumn('building_id');
        });
    }
};
