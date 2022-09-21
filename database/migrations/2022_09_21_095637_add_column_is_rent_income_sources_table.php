<?php

use App\Models\IncomeSource;
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
            $table->boolean('is_rent')->default(0)->after('date_to');
        });

        IncomeSource::withTrashed()
            ->whereIsRent(0)
            ->update([
                'is_rent' => 1,
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('income_sources', function (Blueprint $table) {
            $table->dropColumn('is_rent');
        });
    }
};
