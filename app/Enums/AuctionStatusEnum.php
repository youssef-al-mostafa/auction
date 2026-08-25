<?php

namespace App\Enums;

enum AuctionStatusEnum: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case RUNNING = 'running';
    case ENDED = 'ended';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::SCHEDULED, self::RUNNING],
            self::SCHEDULED => [self::RUNNING, self::DRAFT],
            self::RUNNING => [self::ENDED],
            self::ENDED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
