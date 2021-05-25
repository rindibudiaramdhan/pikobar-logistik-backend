<?php

namespace App\Http\Requests;

use App\Enums\LogisticRealizationItemsStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogisticRealizationItemUpdateRequest extends FormRequest
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
            'product_id' => 'string',
            'status' => [
                'required',
                'enum:' . LogisticRealizationItemsStatusEnum::class,
            ],
        ];

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
