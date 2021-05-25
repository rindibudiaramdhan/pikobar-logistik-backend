<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogisticRequestStoreRequest extends FormRequest
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
            'master_faskes_id' => 'required|numeric',
            'agency_type' => 'required|numeric',
            'agency_name' => 'required|string',
            'location_district_code' => 'required|string',
            'location_subdistrict_code' => 'required|string',
            'location_village_code' => 'required|string',
            'applicant_name' => 'required|string',
            'primary_phone_number' => 'required|numeric',
            'logistic_request' => 'required',
            'letter_file' => 'required|mimes:jpeg,jpg,png,pdf|max:10240',
            'application_letter_number' => 'required|string'
        ];

        $agencyTypeExcept = [1, 2, 3];
        if (in_array($this->agency_type, $agencyTypeExcept)) {
            $param['total_covid_patients'] = 'required|numeric';
            $param['total_isolation_room'] = 'required|numeric';
            $param['total_bedroom'] = 'required|numeric';
            $param['total_health_worker'] = 'required|numeric';
        }

        return $param;
    }
}
