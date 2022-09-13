<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\IncomesFile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Files extends Controller
{
    /**
     * Модель файла
     * 
     * @var \App\Models\File|\App\Models\IncomesFile
     */
    protected $model;

    /**
     * Инициализация объекта
     * 
     * @param  \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->model = (bool) $request->income ? new IncomesFile : new File;
    }

    /**
     * Формирует строку с файлом на вывод
     * 
     * @param  \App\Models\IncomesFile|\App\Models\File $row
     * @return \App\Models\IncomesFile|\App\Models\File
     */
    public function getFileRow(IncomesFile|File $row)
    {
        $hash = encrypt(request()->bearerToken());

        $url = "api/1.0/download/income/file";

        if ($this->model instanceof File)
            $url = "api/1.0/download/file";

        $row->url = Str::finish(env("APP_URL", "http://localhost"), "/") . "{$url}/{$hash}?id=" . $row->id;

        return $row;
    }

    /**
     * Формирует имя файла
     * 
     * @param  string|null $extension
     * @return string
     */
    public function createFileName($extension = null)
    {
        return (bool) $extension ? Str::finish(Str::orderedUuid(), "." . Str::lower($extension)) : Str::orderedUuid();
    }

    /**
     * Смена имени
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rename(Request $request)
    {
        $request->validate([
            'name' => "required",
        ]);

        if (!$file = $this->model->find($request->id))
            return response()->json(['message' => "Файл не найден или уже удален"], 400);

        $file->name = $file->extension
            ? Str::finish($request->name, ".{$file->extension}") : $request->name;

        $file->save();

        return response()->json(
            $this->getFileRow($file)
        );
    }

    /**
     * Удаление файла
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function drop(Request $request)
    {
        if (!$file = $this->model->find($request->id))
            return response()->json(['message' => "Файл не найден или уже удален"], 400);

        $file->delete();

        return response()->json(
            $this->getFileRow($file)
        );
    }

    /**
     * Восстановление удаленного файла
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reestablish(Request $request)
    {
        if (!$file = $this->model->withTrashed()->find($request->id))
            return response()->json(['message' => "Файл не найден"], 400);

        $file->restore();

        return response()->json(
            $this->getFileRow($file)
        );
    }
}
