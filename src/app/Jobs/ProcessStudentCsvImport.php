<?php

namespace App\Jobs;

use App\Models\Student;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Log;

class ProcessStudentCsvImport implements ShouldQueue
{
    use Queueable;

    /**
     * ジョブ最大試行回数
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $csvPath
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // ジョブを意図的に失敗させたいときは、以下のコメントアウトを外す
        // $this->fail();
        // Log::error('CSVファイルのデータが登録されませんでした。');
        // return;

        $csvFullPath = Storage::disk('local')->path($this->csvPath);
        if (!file_exists($csvFullPath)) {
            Log::error('該当のCSVファイルが見つかりませんでした。');
            return ;
        }

        // csvファイルを開く
        $csv = fopen($csvFullPath, 'r');
        // csvファイルのヘッダーを取り除く
        $this->removeCsvHeader($csv);

        $rows = collect();
        $columns = collect(['first_name', 'last_name', 'email']);
        // 一括登録する生徒のデータを準備
        //
        // 例）
        // [
        //     [
        //         'first_name' => '山田',
        //         'last_name' => '太郎',
        //         'email' => 'TaroYamada@example.com'
        //     ],
        //     ......
        // ]
        while (($row = fgetcsv($csv)) !== false) {
            $rows->push($columns->combine($row));
        }

        // csvファイルを閉じる
        fclose($csv);

        $existedStudentEmails = Student::query()
            ->pluck('email');

        // 既にメールアドレスで登録されている生徒は除外する
        $filteredRows = $rows->filter(fn ($row) =>
            !$existedStudentEmails->contains($row['email'])
        )->toArray();

        if (empty($filteredRows)) {
            return ;
        }

        DB::beginTransaction();
        try {
            DB::table('students')->insert($filteredRows);

            DB::commit();
            Log::info('CSVファイルのデータが登録されました。');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CSVファイルのデータが登録されませんでした。', [
                'エラーメッセージ' => $e->getMessage(),
            ]);
        }
    }

    private function removeCsvHeader($csv): void {
        fgetcsv($csv);
    }
}
