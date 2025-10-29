<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Définir la logique d'autorisation si nécessaire
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            // Assurez-vous que l'utilisateur n'est pas déjà attribué à ces tickets
            'ticketinstance_ids' => 'required|array|min:1', 
            'ticketinstance_ids.*' => 'integer|exists:ticketinstances,id',
        ];
    }
}