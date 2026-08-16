<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'month' => ['required', 'date_format:Y-m'],
        ];

        if ($this->isMethod('post')) {
            $rules['category_id'] = [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where(fn ($q) => $q->where('user_id', $this->user()->id)->orWhereNull('user_id'));
                }),
            ];
        }

        return $rules;
    }
}
