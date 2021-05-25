<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadLetterRequest extends FormRequest
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
            'letter_file' => 'required|mimes:jpeg,jpg,png,pdf|max:10240',
            'agency_id' => 'required',
            'applicant_id' => 'required',
            'update_type' => 'required'
        ];
    }
}
