<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'scheduled_time' => $this->scheduled_time,
            'timezone' => $this->timezone,
            'all_day' => $this->all_day,
            'is_recurring' => $this->is_recurring,
            'source_channel' => $this->source_channel,
            'task_list_id' => $this->task_list_id,
            'google_task_list_id' => $this->google_task_list_id,
            'google_task_list_title' => $this->google_task_list_title,
            'recurrence' => $this->whenLoaded('recurrence', function () {
                return [
                    'type' => $this->recurrence->recurrence_type,
                    'interval' => $this->recurrence->interval_value,
                    'day_of_week' => $this->recurrence->day_of_week,
                    'day_of_month' => $this->recurrence->day_of_month,
                ];
            }),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
