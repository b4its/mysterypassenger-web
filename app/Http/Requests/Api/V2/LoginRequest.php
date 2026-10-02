<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'login' => ['required_without_all:email,username', 'nullable', 'string'],
            'email' => ['required_without_all:login,username', 'nullable', 'string'],
            'username' => ['required_without_all:login,email', 'nullable', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function loginIdentifier(): string
    {
        return (string) ($this->input('login') ?? $this->input('username') ?? $this->input('email'));
    }
}
