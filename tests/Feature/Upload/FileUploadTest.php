<?php

namespace Tests\Feature\Upload;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
