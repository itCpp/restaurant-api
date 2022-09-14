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
        Schema::table('employees', function (Blueprint $table) {
            $table->index('job_title');
            $table->index('employee_otdel_id');
            $table->index(['employee_otdel_id', 'job_title'], "otdel_job_titles");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['job_title']);
            $table->dropIndex(['employee_otdel_id']);
            $table->dropIndex('otdel_job_titles');
        });
    }
};
