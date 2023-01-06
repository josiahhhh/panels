<?php

namespace Pterodactyl\Http\Requests\Admin\Alert;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlertRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'message' => ['required', 'min:2'],
            'type' => ['required', Rule::in(['info', 'success', 'warning', 'error'])],
            'node_ids' => ['required', 'array'],
            'node_ids.*' => ['integer', 'exists:nodes,id'],
            'created_at' => ['required', 'date'],
            'expire_at' => ['required', 'date'],
            'delete_when_expired' => ['sometimes', 'integer'],
        ];
    }
}
