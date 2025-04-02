<?php

namespace Tests\Feature;

use App\Jobs\ProcessStudentCsvImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CsvBulkStoreControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // ユーザー作成
        $this->user = User::factory()->create();
        // ストレージとキューのモックを作成
        Storage::fake('local');
        Queue::fake();
    }

    #[Test]
    public function ファイルがアップロードされた後に、ジョブが実行されていること(): void
    {
        // csvファイルを作成
        $csvContent = "first_name,last_name,email\n山田,太郎,TaroYamada@example.com\n本田,次郎,ZiroHonda@example.com";
        $csv = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson(route('bulk-students.csv-upload'), [
                'csv' => $csv,
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'message' => 'ファイルのアップロードに成功しました。データの反映までしばらくお待ちください。'
            ]);

        // ジョブが投入されていることを検証
        Queue::assertPushed(ProcessStudentCsvImport::class);
        // 投入されたジョブの合計数を検証
        Queue::assertCount(1);
    }
}
