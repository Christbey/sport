<?php
// ListCollegeBasketballRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListCollegeBasketballRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'game_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}