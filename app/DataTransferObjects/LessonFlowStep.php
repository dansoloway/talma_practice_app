<?php

namespace App\DataTransferObjects;

class LessonFlowStep
{
    public function __construct(
        public readonly string $type,
        public readonly int|string|null $activityId,
        public readonly string $label,
        public readonly ?string $subdetail = null,
        public readonly int $sortOrder = 0,
    ) {}

    public function key(): string
    {
        return $this->type . ':' . ($this->activityId ?? '0');
    }

    public function matchesEvent(string $activityType, ?int $activityId): bool
    {
        if ($this->type === 'vocabulary') {
            return $activityType === 'vocabulary';
        }

        if ($this->type !== $activityType) {
            return false;
        }

        if ($this->type === 'prompts') {
            return $activityId === null || $activityId === 0;
        }

        return (int) $this->activityId === (int) $activityId;
    }

    public function isCompletedKey(): string
    {
        return $this->type . ':' . ($this->activityId ?? 0);
    }
}
