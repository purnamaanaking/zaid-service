<?php

namespace Tests\Feature\Upload;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    public function test_verified_user_can_upload_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->active()->create();

        $response = $this->actingAs($user, 'sanctum')->post('/api/v1/upload', [
            'file' => UploadedFile::fake()->image('schedule.jpg', 800, 600),
            'type' => 'image',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.type', 'image')
            ->assertJsonStructure(['data' => ['url', 'path', 'mime_type', 'size']]);
    }

    public function test_verified_user_can_upload_csv_schedule(): void
    {
        Storage::fake('public');
        $user = User::factory()->active()->create();
        $file = UploadedFile::fake()->createWithContent('agenda.csv', "Tanggal,Acara\n2026-08-23,Lomba gemastik");

        $this->actingAs($user, 'sanctum')->post('/api/v1/upload', ['file' => $file, 'type' => 'document'])
            ->assertOk()
            ->assertJsonPath('data.type', 'document')
            ->assertJsonPath('data.extracted_text', 'Tanggal,Acara 2026-08-23,Lomba gemastik');
    }

    public function test_verified_user_can_upload_xlsx_schedule(): void
    {
        Storage::fake('public');
        $user = User::factory()->active()->create();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([['Tanggal', 'Acara'], ['2026-08-23', 'Lomba gemastik']]);
        $path = tempnam(sys_get_temp_dir(), 'agenda').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $file = new UploadedFile($path, 'agenda.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($user, 'sanctum')->post('/api/v1/upload', ['file' => $file, 'type' => 'document'])
            ->assertOk()
            ->assertJsonPath('data.extracted_text', 'Tanggal | Acara 2026-08-23 | Lomba gemastik');
    }

    public function test_unverified_user_cannot_upload(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->post('/api/v1/upload', [
            'file' => UploadedFile::fake()->image('test.jpg'),
            'type' => 'image',
        ]);

        $response->assertForbidden();
    }
}
