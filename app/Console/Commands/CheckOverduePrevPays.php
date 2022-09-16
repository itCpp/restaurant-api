<?php

namespace App\Console\Commands;

use App\Http\Controllers\Incomes;
use App\Models\IncomeSource;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class CheckOverduePrevPays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pays:overdue {source_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Находит просроченные платежи предыдущих месяцев';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        IncomeSource::where('is_free', false)
            ->where('date', '<=', now()->format("Y-m-d"))
            ->where(function ($query) {
                $query->where('date_to', '>=', now()->format("Y-m-d"))
                    ->orWhere('date_to', null);
            })
            ->when((bool) $this->argument('source_id'), function ($query) {
                $query->where('id', $this->argument('source_id'));
            })
            ->get()
            ->each(function ($row) {
                $row->is_overdue = (new Incomes)->isOverdue($row);
                $row->save();
            });

        return 0;
    }
}
