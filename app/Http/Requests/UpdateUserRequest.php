<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class UpdateUserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'phone' => ['string', 'max:255'],
            'adresse' => ['string', 'max:255'],
            'photo' => ['string', 'max:255'],
            'email' => ['email', 'max:255', Rule::unique(User::class)],
        ];
    }
}
