<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;
use App\RequestLetter;
use App\Http\Controllers\Controller;
use DB;
use App\LogisticRealizationItems;
use App\Applicant;
use App\Enums\ApplicantStatusEnum;
use App\Http\Requests\RequestLetterListRequest;
use App\Http\Requests\RequestLetterStoreRequest;
use App\Http\Requests\RequestLetterUpdateRequest;

class RequestLetterController extends Controller
{
    public function index(RequestLetterListRequest $request)
    {
        $data = [];
        $limit = $request->input('limit', 10);
        $defaultField = $this->defaultField();
        $defaultField[] = 'applicants.verification_status';
        $data = RequestLetter::select($defaultField);
        $data = $this->defaultJoinTable($data);
        $data = $data->where('request_letters.outgoing_letter_id', $request->outgoing_letter_id)
                      ->where(function ($query) use ($request) {
                          if ($request->has('application_letter_number')) {
                            $query->where('applicants.application_letter_number', 'LIKE', "%{$request->input('application_letter_number')}%");
                          }
                      })
                      ->where('verification_status', '=', ApplicantStatusEnum::verified())
                      ->where('applicants.approval_status', '=', ApplicantStatusEnum::approved())
                      ->whereNotNull('applicants.finalized_by');

        $data = $data->orderBy('request_letters.id')->paginate($limit);
        foreach ($data as $key => $val) {
            $data[$key] = $this->getRealizationData($val);
        }
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    public function show($id)
    {
        $data = [];
        $defaultField = $this->defaultField();
        $requestLetter = RequestLetter::select($defaultField);
        $requestLetter = $this->defaultJoinTable($requestLetter);
        $requestLetter = $requestLetter->where('request_letters.id', $id);
        $requestLetter = $requestLetter->orderBy('request_letters.id')->get();

        foreach ($requestLetter as $key => $val) {
            $data[] = $this->getRealizationData($val);
        }

        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    public function store(RequestLetterStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $request_letter = RequestLetter::requestLetterStore($request);
            $response = ['request_letter' => $request_letter];
            DB::commit();
            $response = response()->format(Response::HTTP_OK, 'success', $response);
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }
        return $response;
    }

    public function update(RequestLetterUpdateRequest $request, $id)
    {
        try {
            $data = RequestLetter::find($id);
            $data->applicant_id = $request->applicant_id;
            $data->save();
            $response = response()->format(Response::HTTP_OK, 'success');
        } catch (\Exception $exception) {
            $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }
        return $response;
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $deleteRealization = RequestLetter::where('id', $id)->delete();
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }
        return response()->format(Response::HTTP_OK, 'success', ['id' => $id]);
    }

    /**
     * searchByLetterNumber function
     *
     * Menampilkan list surat permohonan yang belum didaftarkan di Surat Perintah.
     * opsional, jika parameter request_letter_id dikirim, maka surat permohonan dengan ID tersebut akan tetap muncul di list
     *
     * @param Request $request
     * @return void
     */
    public function searchByLetterNumber(Request $request)
    {
        $data = [];
        $request_letter_ignore = $request->input('request_letter_id');
        try {
            $list = Applicant::select('id', 'application_letter_number', 'verification_status', 'approval_status')
                ->where(function ($query) use ($request) {
                    if ($request->has('application_letter_number')) {
                        $query->where('application_letter_number', 'LIKE', "%{$request->input('application_letter_number')}%");
                    }
                })
                ->where('is_deleted', '!=', 1)
                ->where('verification_status', '=', ApplicantStatusEnum::verified())
                ->where('approval_status', '=', ApplicantStatusEnum::approved())
                ->where('application_letter_number', '!=', '')
                ->whereNotNull('finalized_by')
                ->get();
            //filterization
            $data = $this->checkAlreadyPicked($list, $request_letter_ignore);
        } catch (\Exception $exception) {
            return response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }

        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    /**
     * getRealizationData
     *
     */
    public function getRealizationData($request_letter)
    {
        $realization_total = LogisticRealizationItems::where('agency_id', $request_letter->agency_id)
        ->where('applicant_id', $request_letter->applicant_id)
        ->sum('realization_quantity');


        $realization = LogisticRealizationItems::where('agency_id', $request_letter->agency_id)
        ->where('applicant_id', $request_letter->applicant_id)
        ->whereNotNull('realization_date')
        ->first();

        $request_letter->realization_total = $realization_total;
        $request_letter->realization_date = $realization['realization_date'];

        $data = $request_letter;
        return $data;
    }

    /**
     * This function is to check number letter already pick or not
     * return array of object
     */
    public function checkAlreadyPicked($list, $request_letter_ignore)
    {
        $data = [];
        foreach ($list as $key => $value) {
            if ($request_letter_ignore == $value['id']) {
                $data[] = $value;
            } else {
                $data[] = getRequestLetter($value)
            }
        }

        return $data;
    }

    public function getRequestLetter($value)
    {
        $find = RequestLetter::where('applicant_id', $value['id'])->first();
        if (!$find) {
            $data[] = $value;
        }

        return $data;
    }

    public function defaultField()
    {
        return [
            'request_letters.id',
            'request_letters.outgoing_letter_id',
            'request_letters.applicant_id',
            'applicants.application_letter_number',
            'applicants.agency_id',
            'agency.agency_name',
            'districtcities.kemendagri_kabupaten_nama',
            'applicants.applicant_name',
            'agency.location_district_code',
            DB::raw('0 as realization_total'),
            DB::raw('"" as realization_date')
        ];
    }

    public function defaultJoinTable($data)
    {
        $data = $data->join('applicants', 'applicants.id', '=', 'request_letters.applicant_id')
        ->join('agency', 'agency.id', '=', 'applicants.agency_id')
        ->join('districtcities', 'districtcities.kemendagri_kabupaten_kode', '=', 'agency.location_district_code');
        return $data;
    }
}
