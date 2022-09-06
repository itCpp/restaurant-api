<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use App\Models\File;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Files extends Controller
{
    /**
     * Список файлов по расходу
     * 
     * @param  \Illumniate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $files = File::whereCashboxId($request->id)
            ->orderBy('id', "DESC")
            ->get()
            ->map(function ($row) {
                return $row;
            });

        return response()->json([
            'files' => $files,
        ]);
    }

    /**
     * Загрузка нового файла
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $file = new File;

        $file_name = $this->createFileName($request->file->extension());
        $path = "files/" . now()->format("Y/m/d");

        while (Storage::exists($path . "/" . $file_name))
            $file_name = $this->createFileName($request->file->extension());

        $file->cashbox_id = (int) $request->cashboxId;
        $file->name = $request->name;
        $file->file_name = $file_name;
        $file->path = $path;
        $file->extension = $request->file->extension();
        $file->mime_type = $request->file->getType();
        $file->size = $request->file->getSize();

        try {
            $file->md5_hash = md5_file($request->file->getPathname());
        } catch (Exception) {
        }

        $file->user_id = $request->user()->id;

        if ($file->md5_hash and $reply = File::where('md5_hash', $file->md5_hash)->first()) {

            $file->file_name = $reply->file_name;
            $file->path = $reply->path;
        } else {

            try {
                $request->file->storeAs($path, $file_name);
            } catch (Exception $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 400);
            }
        }

        $file->save();

        return response()->json([
            'file' => $file,
        ]);
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
}
