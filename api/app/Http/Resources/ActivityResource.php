<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Activitylog\Models\Activity;

/**
 * @mixin Activity
 */
final class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{attributes?: array<string, mixed>, old?: array<string, mixed>} $changes */
        $changes = $this->attribute_changes ?? [];

        $causer = $this->causer;

        return [
            'id' => $this->id,
            'event' => $this->event,
            'causer' => $causer instanceof User ? $causer->name : null,
            'changes' => $changes['attributes'] ?? [],
            'previous' => $changes['old'] ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
