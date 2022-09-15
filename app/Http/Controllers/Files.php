<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\IncomesFile;
use App\Models\Log;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

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
     * @param  \App\Models\File|\App\Models\IncomesFile $row
     * @return \App\Models\File|\App\Models\IncomesFile
     */
    public function getFileRow(File|IncomesFile $row)
    {
        $url = Str::finish(env("APP_URL", "http://localhost"), "/");

        if ($row instanceof IncomesFile)
            $url .= "api/1.0/download/file/income/";
        else
            $url .= "api/1.0/download/file/";

        $url .= encrypt(request()->bearerToken());

        $row->url = $url . "?id=" . $row->id;

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

        Log::write($file, $request);

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

        Log::write($file, $request);

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

        Log::write($file, $request);

        return response()->json(
            $this->getFileRow($file)
        );
    }

    /**
     * Скачивание файла
     * 
     * @param  \Illumniate\Http\Request $request
     * @param  string $type
     * @param  null|string $name
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * 
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function download(Request $request, $type, $name = null)
    {
        if ($type === "income") {
            $model = new IncomesFile;
        } else {
            $model = new File;
            $name = $type;
        }

        try {
            $bearer_token = decrypt($name);
            $token_id = explode("|", $bearer_token)[0] ?? null;
        } catch (Exception) {
            abort(403);
        }

        if (!$token = PersonalAccessToken::find($token_id))
            abort(403);

        if ($token->expires_at and $token->expires_at < now())
            abort(403);

        if (!$row = $model->find($request->id))
            abort(404);

        $path = storage_path("app/{$row->path}/{$row->file_name}");

        return response()->file($path);
    }
}
