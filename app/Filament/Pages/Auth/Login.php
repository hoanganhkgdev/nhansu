<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

/** The old site logged in by username; accept either a username or an email address. */
class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Tên đăng nhập hoặc email')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $field = str_contains($data['email'], '@') ? 'email' : 'username';

        return [
            $field => $data['email'],
            'password' => $data['password'],
        ];
    }
}
