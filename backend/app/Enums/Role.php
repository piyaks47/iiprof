<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Editor => 'Editor',
            self::User => 'User',
        };
    }

    /** Every role signs in at /admin and gets its own dashboard. */
    public function canAccessAdmin(): bool
    {
        return true;
    }

    /** Admins and editors manage website pages. */
    public function canManageContent(): bool
    {
        return in_array($this, [self::Admin, self::Editor], true);
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Everything: users, user types, modules, pages and settings.',
            self::Editor => 'Website pages plus the modules of their user type.',
            self::User => 'Their own dashboard and the modules of their user type.',
        };
    }

    /** @return array<string, string> value => label, for selects */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
