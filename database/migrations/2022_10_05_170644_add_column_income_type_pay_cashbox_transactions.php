<?php

use App\Models\CashboxTransaction;
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
        Schema::table('cashbox_transactions', function (Blueprint $table) {
            $table->string("income_type_pay", 100)->nullable()->after('expense_subtype_id');
        });

        CashboxTransaction::where('is_income', true)
            ->update(["income_type_pay" => "tenant"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cashbox_transactions', function (Blueprint $table) {
            $table->dropColumn('income_type_pay');
        });
    }
};
