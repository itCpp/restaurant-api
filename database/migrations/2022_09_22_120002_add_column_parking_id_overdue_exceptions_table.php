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
        Schema::table('overdue_exceptions', function (Blueprint $table) {
            $table->bigInteger('parking_id')->nullable()->after('purpose_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('overdue_exceptions', function (Blueprint $table) {
            $table->dropColumn('parking_id');
        });
    }
};
