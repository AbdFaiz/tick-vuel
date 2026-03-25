<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TicketReplyStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string',
            'status' => auth()->user()->role == 'admin' ? 'required|in:open,onprogress,rejected,resolved' : 'nullable',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx,zip|max:5120',
        ];
    }
}
