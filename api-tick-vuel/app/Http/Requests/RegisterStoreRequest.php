<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    // public function messages(): array
    // {
    //     return [
    //         'name.required' => 'Name is required',
    //         'email.required' => 'Email is required',
    //         'email.email' => 'Email is invalid',
    //         'email.unique' => 'Email is already taken',
    //         'password.required' => 'Password is required',
    //         'password.min' => 'Password must be at least 8 characters',
    //         'password.confirmed' => 'Password confirmation does not match',
    //     ];
    // }
}
