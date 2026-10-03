<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'type'        => [$required, Rule::enum(TransactionType::class)],
            'amount'      => [$required, 'numeric', 'gt:0', 'max:9999999999.99', 'decimal:0,2'],
            'currency'    => ['sometimes', 'string', 'size:3'],
            'description' => [$required, 'string', 'max:255'],
            'notes'       => ['nullable', 'string', 'max:5000'],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency')) {
            $this->merge(['currency' => strtoupper($this->currency)]);
        }
    }
}