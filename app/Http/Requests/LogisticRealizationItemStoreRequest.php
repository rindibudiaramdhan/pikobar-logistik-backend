<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogisticRealizationItemStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $param = [
            'agency_id' => 'numeric',
            'applicant_id' => 'numeric',
            'need_id' => 'numeric',
            'status' => 'string',
            'realization_quantity' => 'numeric',
            'realization_date' => 'date',
        ];

        if ($this->has('by_admin')) {
            $param['product_id'] = 'string';
            $param['usage'] = 'string';
            $param['priority'] = 'string';
        }

        if ($this->input('store_type') === 'recommendation') {
            $extra = [
                'recommendation_quantity' => 'numeric',
                'recommendation_date' => 'date',
                'recommendation_unit' => 'string',
            ];
        }
        $param = array_merge($extra, $param);
        return $param;
    }
}
