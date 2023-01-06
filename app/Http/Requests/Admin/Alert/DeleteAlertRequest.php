<?php

namespace Pterodactyl\Http\Requests\Admin\Alert;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAlertRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => ['required', 'integer', 'exists:alerts,id'],
        ];
    }
}
