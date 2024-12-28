<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LedgerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Set to true or apply authorization logic if needed.
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'group_id' => [
                'required',
                // 'uuid',
                'exists:groups,id', // assumes 'groups' is the table and 'id' is the primary key
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:ledgers,name,' . $this->id,
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                'unique:ledgers,code,' . $this->id,
            ],
            'op_balance' => [
                'required',
                'numeric',
                'min:0',
                'regex:/^[0-9]{0,23}(\.[0-9]{1,2})?$/',
            ],
            'op_balance_dc' => [
                'required',
                'in:D,C',
            ],
            'type' => [
                'required',
                'boolean',
                'max:2',
            ],
            'reconciliation' => [
                'required',
                'boolean',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'group_id.required' => 'Parent group cannot be empty',
            'group_id.integer' => 'Parent group is not a valid number',
            'group_id.exists' => 'Parent group is not valid',
            'group_id.max' => 'Parent group id length cannot be more than 18',

            'name.required' => 'Ledger name cannot be empty',
            'name.unique' => 'Ledger name is already in use',
            'name.max' => 'Ledger name cannot be more than 255 characters',

            'code.unique' => 'Ledger code is already in use',
            'code.max' => 'Ledger code cannot be more than 255 characters',

            'op_balance.required' => 'Opening balance cannot be empty',
            'op_balance.numeric' => 'Opening balance is not a valid amount',
            'op_balance.regex' => 'Opening balance length cannot be more than 28',
            'op_balance.min' => 'Opening balance cannot be less than 0.00',

            'op_balance_dc.required' => 'Opening balance Dr/Cr cannot be empty',
            'op_balance_dc.in' => 'Invalid value for opening balance Dr/Cr',

            'type.required' => 'Bank or cash account cannot be empty',
            'type.boolean' => 'Invalid value for bank or cash account',
            'type.max' => 'Bank or cash account cannot be more than 2 integers',

            'reconciliation.required' => 'Reconciliation cannot be empty',
            'reconciliation.boolean' => 'Invalid value for reconciliation',

            'notes.max' => 'Notes length cannot be more than 500',
        ];
    }
}
