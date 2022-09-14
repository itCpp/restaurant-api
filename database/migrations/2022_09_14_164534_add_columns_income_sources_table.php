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
            $table->date('date_to')->nullable()->comment('Дата окончания договора')->after('date');
            $table->boolean('is_parking')->default(false)->comment('Аренда парковки')->after('is_free');
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
            $table->dropColumn('date_to');
            $table->dropColumn('is_parking');
        });
    }
};
