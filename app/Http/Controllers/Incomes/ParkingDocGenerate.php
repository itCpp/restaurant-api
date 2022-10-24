<?php

namespace App\Http\Controllers\Incomes;

use PhpOffice\PhpWord\PhpWord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ParkingDocGenerate
{
    /**
     * PHP Word
     * 
     * @var \PhpOffice\PhpWord\PhpWord
     */
    protected $word;

    /**
     * Инициализация объекта генератора
     * 
     * @return void
     */
    public function __construct()
    {
        $this->word = new PhpWord;
    }

    /**
     * Генерация документа
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function generate(Request $request)
    {
        $section = $this->word->addSection();

        foreach ((new Parking)->index($request, true) as $row) {

            $section->addText($row->name, ['name' => 'Tahoma', 'size' => 14, 'bold' => true]);

            foreach ($row->parking ?? [] as $key => $car) {

                $text = ($key + 1) . ".";
                $text .= " " . $car->car ?? "Не указана";
                $text .= " (" . ($car->car_number ?? "Номер не указан") . ")";

                $section->addText(trim($text), ['name' => 'Tahoma', 'size' => 14]);
            }

            $section->addTextBreak();
        }

        $file = "Список автомобилей " . now()->format("d.m.Y H:i") . ".docx";

        header("Content-Description: File Transfer");
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($this->word, 'Word2007');
        $objWriter->save("php://output");
    }
}
