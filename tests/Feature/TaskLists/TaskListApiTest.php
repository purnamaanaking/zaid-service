<?php

namespace Tests\Feature\TaskLists;

use App\Models\User;
use Tests\TestCase;

class TaskListApiTest extends TestCase
{
    public function test_user_can_manage_own_task_list(): void
    {
        $user = User::factory()->active()->create();

        $list = $this->actingAs($user, 'sanctum')->postJson('/api/v1/task-lists', ['name' => 'Kuliah'])
            ->assertCreated()->json('data.task_list');

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/task-lists')
            ->assertOk()->assertJsonPath('data.items.0.id', $list['id']);

        $this->actingAs($user, 'sanctum')->patchJson("/api/v1/task-lists/{$list['id']}", ['name' => 'Campus'])
            ->assertOk()->assertJsonPath('data.task_list.name', 'Campus');

        $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/task-lists/{$list['id']}")->assertOk();
    }
}
