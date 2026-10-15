<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_NONE = 'none';

    public const ROLE_DESCRIPTIONS = [
        self::ROLE_SUPER_ADMIN => 'Toàn quyền, kể cả phân quyền cho người khác.',
        self::ROLE_ADMIN => 'Quản lý dữ liệu nhân sự và tài khoản.',
        self::ROLE_EDITOR => 'Nhập và sửa dữ liệu nhân sự, không quản lý tài khoản.',
        self::ROLE_NONE => 'Không vào được trang quản trị.',
    ];

    public const ROLES = [
        self::ROLE_SUPER_ADMIN => 'Super admin',
        self::ROLE_ADMIN => 'Quản trị',
        self::ROLE_EDITOR => 'Biên tập',
        self::ROLE_NONE => 'Không có quyền quản trị',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->role !== self::ROLE_NONE;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /** True while the password is still the hash imported from NukeViet (upgraded to bcrypt on first login). */
    public function hasLegacyPassword(): bool
    {
        return str_starts_with((string) $this->password, '{SSHA512}');
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN], true);
    }
}
