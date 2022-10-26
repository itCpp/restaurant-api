<?php

namespace App\Http\Controllers\Cashbox;

use App\Http\Controllers\Controller;
use App\Models\Base\CrmKassa;
use App\Models\Base\CrmKassaInkassatsiyaUpp;
use App\Models\Base\CrmLegery;
use App\Models\Base\Office;
use Illuminate\Http\Request;

class Base extends Controller
{
    /**
     * Выводит данные из БАЗЫ
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json(
            $this->getData($request),
        );
    }

    /**
     * Формиует данные для вывода
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function getData(Request $request)
    {
        $safe = CrmKassaInkassatsiyaUpp::selectRaw('sum(sum) as sum, office as office_id')
            ->where([
                ['confirm', 1],
                ['done', 1],
                ['del', 0],
            ])
            ->groupBy('office')
            ->having('sum', '>', 0)
            ->get()
            ->map(function ($row) {
                $row->office = Office::find($row->office_id);
                return $row;
            });

        return [
            'safe' => $safe,
            'cashbox' => $this->getCashboxSum($request),
        ];
    }

    /**
     * Подсчет данных в кассе
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function getCashboxSum(Request $request)
    {
        $data = [];

        CrmKassa::selectRaw("
            SUM(IF(vidOperacii IN('нал', 'доп/нал'), uppSumma + crm_kassa.predstavRashod, 0)) as nal,
            SUM(IF(vidOperacii IN('безнал', 'доп/безнал'), uppSumma + crm_kassa.predstavRashod, 0)) as beznal, 
            SUM(IF(vidOperacii IN('доп/корп карта', 'корп карта'), uppSumma + crm_kassa.predstavRashod, 0)) as kk,
            SUM(IF(vidOperacii IN('Кредит Альфа банк', 'Рассрочка Альфа банк', 'рассрочка Восточный экспресс', 'кредит Восточный экспресс', 'кредит ОТП Банк', 'рассрочка ОТП Банк'), uppSumma + crm_kassa.predstavRashod, 0)) as credit,
            SUM(IF(vidOperacii IN('р/с', 'доп/р/с'), uppSumma + crm_kassa.predstavRashod, 0)) as rs,
            SUM(IF(vidOperacii IN('нал', 'доп/нал'), crm_kassa.rashod, 0)) as rashod,
            SUM(inkassacia) as inkassacia,
            crm_kassa.company
        ")
            ->leftjoin('crm_agreement', function ($join) {
                $join->on('crm_kassa.nomerDogovora', '=', 'crm_agreement.nomerDogovora')
                    ->where('crm_agreement.styles', 'NOT LIKE', '#ff0001%');
            })
            ->where('crm_kassa.date', now()->format("Y-m-d"))
            ->groupBy('crm_kassa.company')
            ->get()
            ->each(function ($row) use (&$data) {

                $office = Office::where('oldId', $row->company)->first();

                $row->offfice_id = $office->id ?? null;
                $row->office = $office;

                $data[$row->company] = $row;
            });

        CrmLegery::selectRaw('sum(money) as salary, company')
            ->where('date', now()->format("Y-m-d"))
            ->where(function ($query) {
                $query->where('type', 'like', "ЗП")
                    ->orWhere('type', 'like', "Надбавка к ЗП")
                    ->orWhere('type', 'like', "Аванс")
                    ->orWhere('type', 'like', "Премия")
                    ->orWhere('type', 'like', "Премия ЦПП");
            })
            ->groupBy('company')
            ->get()
            ->each(function ($row) use (&$data) {
                $data[$row->company]->salary = (float) $row->salary;
            });

        return collect($data)->map(function ($row) {

            $row->safe = CrmKassaInkassatsiyaUpp::where([
                ['confirm', 1],
                ['done', 1],
                ['type', 1],
                ['del', 0],
                ['office', $row->office_id]
            ])->whereBetween('confirm_time', [
                now()->format("Y-m-d 00:00:00"),
                now()->format("Y-m-d 23:59:59")
            ])->sum('sum');

            $row->cash = ($row->nal ?? 0) - ($row->rashod ?? 0) - ($row->salary ?? 0) - ($row->inkassacia ?? 0) - ($row->safe ?? 0);

            return $row;
        })->values()->all();
    }
}
