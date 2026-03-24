<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422)
        );
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
