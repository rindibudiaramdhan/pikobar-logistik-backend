<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterFaskesRequest extends FormRequest
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
            'nomor_izin_sarana' => 'required',
            'nama_faskes' => 'required',
            'id_tipe_faskes' => 'required',
            'nama_atasan' => 'required',
            'point_latitude_longitude' => 'string',
            'permit_file' => 'required|mimes:jpeg,jpg,png|max:10240'
        ];
    }
}
