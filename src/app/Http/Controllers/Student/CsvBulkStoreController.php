<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\BulkStoreRequest;
use App\Jobs\ProcessStudentCsvImport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class CsvBulkStoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(BulkStoreRequest $request): JsonResponse
    {
        if (!$request->file('csv')->isValid()) {
            return response()->json(['message' => '不正なファイルです。'], 400);
        }

        // csvファイルをローカルストレージに保存
        $csvPath = $request->file('csv')->store('csvs', 'local');

        if (!$csvPath) {
            return response()->json(
                ['message' => 'ファイルのアップロードに失敗しました。時間おいて再度お試しください。'],
                500
            );
        }

        // ジョブをキューに投入
        ProcessStudentCsvImport::dispatch($csvPath)
            ->delay(Carbon::now()->addSeconds(10));

        return response()->json(
            ['message' => 'ファイルのアップロードに成功しました。データの反映までしばらくお待ちください。'],
            202
        );
    }
}
