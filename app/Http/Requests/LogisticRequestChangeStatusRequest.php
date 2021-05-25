<?php

namespace App\Http\Requests;

use App\Enums\ApplicantStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class LogisticRequestChangeStatusRequest extends FormRequest
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
        return [
            'agency_id' => 'required|numeric',
            'applicant_id' => 'required|numeric',
            'approval_status' => 'required|string',
            'approval_note' => $this->approval_status === ApplicantStatusEnum::rejected() ? 'required' : ''
        ];
    }
}
