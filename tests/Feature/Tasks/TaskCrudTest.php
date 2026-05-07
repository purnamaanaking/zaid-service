<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class TaskCrudTest extends TestCase
{
    public function test_user_can_create_task(): void
    {
        $user = User::factory()->active()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tasks', [
            'title' => 'Laporan Penjualan',
            'scheduled_date' => '2026-05-23',
            'scheduled_time' => '10:00:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.task.title', 'Laporan Penjualan');
    }

    public function test_user_can_list_tasks(): void
    {
        $user = User::factory()->active()->create();
        Task::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 3);
    }

    public function test_user_can_update_task(): void
    {
        $user = User::factory()->active()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.task.title', 'Updated Title');
    }

    public function test_user_can_delete_task(): void
    {
        $user = User::factory()->active()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertOk()
            ->assertJsonPath('data.task_id', $task->id);
    }

    public function test_user_can_complete_task(): void
    {
        $user = User::factory()->active()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/tasks/{$task->id}/complete");

        $response->assertOk()
            ->assertJsonPath('data.task.status', 'completed');
    }
}
