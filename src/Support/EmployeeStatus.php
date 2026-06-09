<?php

namespace Athka\Employees\Support;

class EmployeeStatus
{
    public const ACTIVE = 'ACTIVE';
    public const SUSPENDED = 'SUSPENDED';
    public const ENDED = 'ENDED';
    public const TERMINATED = 'TERMINATED';
    public const RESIGNED = 'RESIGNED';
    public const RETIRED = 'RETIRED';

    public static function statuses(): array
    {
        return [
            self::ACTIVE,
            self::SUSPENDED,
            self::ENDED,
            self::TERMINATED,
            self::RESIGNED,
            self::RETIRED,
        ];
    }

    public static function label(?string $status): string
    {
        return match (strtoupper((string) $status)) {
            self::ACTIVE => tr('Active'),
            self::SUSPENDED => tr('Suspended'),
            self::ENDED => tr('Contract Ended'),
            self::TERMINATED => tr('Terminated'),
            self::RESIGNED => tr('Resigned'),
            self::RETIRED => tr('Retired'),
            default => $status ? tr($status) : tr('Unknown'),
        };
    }

    public static function color(?string $status): string
    {
        return match (strtoupper((string) $status)) {
            self::ACTIVE => 'green',
            self::SUSPENDED => 'orange',
            self::ENDED, self::TERMINATED => 'red',
            self::RESIGNED, self::RETIRED => 'gray',
            default => 'gray',
        };
    }

    public static function badgeType(?string $status): string
    {
        return match (strtoupper((string) $status)) {
            self::ACTIVE => 'success',
            self::SUSPENDED => 'warning',
            self::ENDED, self::TERMINATED => 'danger',
            self::RESIGNED, self::RETIRED => 'neutral',
            default => 'neutral',
        };
    }

    public static function filterOptions(bool $includeAll = false): array
    {
        $options = array_map(
            fn (string $status) => ['value' => $status, 'label' => self::label($status)],
            self::statuses()
        );

        if ($includeAll) {
            array_unshift($options, ['value' => 'all', 'label' => tr('All')]);
        }

        return $options;
    }
}
