<?php

namespace App\Console\Commands;

use App\Http\Controllers\Incomes;
use App\Models\IncomeSource;
use App\Models\PayFine;
use Exception;
use Illuminate\Console\Command;

class CountFinePaysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pays:fine';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проверяет пеню';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->incomes = new Incomes;

        IncomeSource::whereIsFree(false)
            ->where('date', '<', now()->format("Y-m-d"))
            ->where(function ($query) {
                $query->where('date_to', '>=', now()->format("Y-m-d"))
                    ->orWhere('date_to', null);
            })
            ->lazy()
            ->each(function ($row) {
                try {
                    $this->checkSource($row);
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
            });

        return 0;
    }

    /**
     * Обработка строки
     * 
     * @param  \App\Models\IncomeSource $row
     * @return \App\Models\IncomeSource
     */
    public function checkSource($row)
    {
        $row = $this->incomes->getIncomeSourceRow($row);

        $sum = (float) $row->price * (float) $row->space;

        if ((int) $sum == 0) {
            $sum = $row->last->sum ?? 0;
        }

        if (($row->overdue ?? null) and !(bool) ($row->settings['no_fine'] ?? false)) {

            $percent = (float) ($row->settings['fine_percent'] ?? 1);
            $percent = $percent ?: 1;

            PayFine::create([
                'source_id' => $row->id,
                'sum' => round($sum * ($percent / 100), 2),
                'from_sum' => round($sum, 2),
                'fine_percent' => $percent,
                'date' => now()->format("Y-m-d"),
            ]);
        }

        return $row;
    }
}
