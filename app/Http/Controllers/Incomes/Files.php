<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Files as ControllersFiles;
use App\Models\IncomesFile;
use App\Models\Log;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Files extends ControllersFiles
{
    /**
     * Список файлов по расходу
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json([
            'files' => $this->getFilesList($request),
        ]);
    }

    /**
     * Список файлов
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilesList(Request $request)
    {
        return IncomesFile::whereIncomeId($request->id)
            ->orderBy('id', "DESC")
            ->get()
            ->map(function ($row) {
                return $this->getFileRow($row);
            });
    }

    /**
     * Загрузка нового файла
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $file = new IncomesFile;

        $file_name = $this->createFileName($request->file->extension());
        $path = "files/" . now()->format("Y/m/d");

        while (Storage::exists($path . "/" . $file_name))
            $file_name = $this->createFileName($request->file->extension());

        $file->income_id = (int) $request->incomeId;
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

        if ($file->md5_hash and $reply = IncomesFile::where('md5_hash', $file->md5_hash)->first()) {

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

        Log::write($file, $request);

        return response()->json([
            'file' => $this->getFileRow($file),
        ]);
    }
}
