<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Needs;
use App\Agency;
use App\Applicant;
use App\Enums\ApplicantStatusEnum;
use App\LogisticRequest;
use App\FileUpload;
use App\Http\Requests\LogisticRequestChangeStatusRequest;
use App\Http\Requests\LogisticRequestImportRequest;
use App\Http\Requests\LogisticRequestStoreRequest;
use App\Http\Requests\LogisticRequestUrgencyChangeRequest;
use App\Http\Requests\MasterFaskesRequest;
use App\Http\Requests\UploadApplicantFileRequest;
use App\Http\Requests\UploadLetterRequest;
use App\Imports\LogisticImport;
use Maatwebsite\Excel\Facades\Excel;
use App\MasterFaskes;
use App\User;
use App\Validation;
use Log;
use JWTAuth;

class LogisticRequestController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $data = Agency::getList($request, false)->paginate($limit);
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    public function finalList(Request $request)
    {
        $syncSohLocation = \App\PoslogProduct::syncSohLocation();
        $request->merge(['verification_status' => ApplicantStatusEnum::verified()]);
        $request->merge(['approval_status' => ApplicantStatusEnum::approved()]);
        $request->merge(['finalized_by' => ApplicantStatusEnum::finalized()]);
        // Cut Off Logistic Data
        $cutOffDateTimeState = \Carbon\Carbon::createFromFormat(config('wmsjabar.cut_off_format'), config('wmsjabar.cut_off_datetime'))->toDateTimeString();
        $cutOffDateTime = $request->input('cut_off_datetime', $cutOffDateTimeState);
        $today = \Carbon\Carbon::now()->toDateTimeString();

        $request->merge(['start_date' => $cutOffDateTime]);
        $request->merge(['end_date' => $today]);
        $logisticRequest = Agency::getList($request, false)->get();

        $data = [
            'data' => $logisticRequest,
            'total' => count($logisticRequest)
        ];
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    public function store(LogisticRequestStoreRequest $request)
    {
        $request = $this->masterFaskesCheck($request);
        $responseData = LogisticRequest::responseDataStore();
        $response = LogisticRequest::storeProcess($request, $responseData);
        Validation::setCompleteness($request);
        Log::channel('dblogging')->debug('post:v1/logistic-request', $request->all());
        return $response;
    }

    public function update(Request $request, $id)
    {
        $param['agency_id'] = 'required';
        $param['applicant_id'] = 'required';
        $param['update_type'] = 'required';
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $response = LogisticRequest::saveData($request);
            Validation::setCompleteness($request);
        }
        Log::channel('dblogging')->debug('put:v1/logistic-request', $request->all());
        return $response;
    }

    public function show(Request $request, $id)
    {
        $data = Agency::getList($request, false)
                        ->with('letter:id,agency_id,letter')
                        ->where('agency.id', $id)
                        ->firstOrFail();

        $response = response()->format(Response::HTTP_OK, 'success', $data);
        $isNotAdmin = !in_array(JWTAuth::user()->roles, User::ADMIN_ROLE);
        $isDifferentDistrict = $data->location_district_code != JWTAuth::user()->code_district_city;
        if ($isNotAdmin && $isDifferentDistrict) {
            $response = response()->format(Response::HTTP_UNAUTHORIZED, 'Permohonan anda salah, Anda tidak dapat membuka alamat URL tersebut');
        }
        return $response;
    }

    public function listNeed(Request $request)
    {
        $param = ['agency_id' => 'required'];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $response = Needs::listNeed($request);
        }
        return $response;
    }

    public function import(LogisticRequestImportRequest $request)
    {
        $response = LogisticImport::importProcess($request);
        Log::channel('dblogging')->debug('post:v1/logistic-request/import', $request->all());
        return $response;
    }

    public function requestSummary(Request $request)
    {
        $requestSummaryResult['lastUpdate'] = Applicant::active()->createdBetween($request)->filter($request)->first();
        $requestSummaryResult['totalPikobar'] = Applicant::active()->createdBetween($request)->sourceData('pikobar')->filter($request)->count();
        $requestSummaryResult['totalDinkesprov'] = Applicant::active()->createdBetween($request)->sourceData(false)->filter($request)->count();
        $requestSummaryResult['totalUnverified'] = Applicant::active()->createdBetween($request)->unverified()->filter($request)->count();
        $requestSummaryResult['totalApproved'] = Applicant::active()->createdBetween($request)->approved()->filter($request)->count();
        $requestSummaryResult['totalFinal'] = Applicant::active()->createdBetween($request)->final()->filter($request)->count();
        $requestSummaryResult['totalVerified'] = Applicant::active()->createdBetween($request)->verified()->filter($request)->count();
        $requestSummaryResult['totalVerificationRejected'] = Applicant::active()->createdBetween($request)->verificationRejected()->filter($request)->count();
        $requestSummaryResult['totalApprovalRejected'] = Applicant::active()->createdBetween($request)->approvalRejected()->filter($request)->count();

        $data = Applicant::requestSummaryResult($requestSummaryResult);
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    public function changeStatus(LogisticRequestChangeStatusRequest $request)
    {
        $processType = 'verification';
        $changeStatusParam = $this->setChangeStatusParam($request, $param, $processType);
        $param = $changeStatusParam['param'];
        $processType = $changeStatusParam['processType'];
        $dataUpdate = $changeStatusParam['dataUpdate'];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $response = LogisticRequest::changeStatus($request, $processType, $dataUpdate);
            Validation::setCompleteness($request);
        }

        Log::channel('dblogging')->debug('post:v1/logistic-request/' . $processType, $request->all());
        return $response;
    }

    public function setChangeStatusParam(Request $request, $param, $processType)
    {
        $changeStatusParam = [
            'param' => [
                'approval_status' => 'required|string',
                'approval_note' => $request->approval_status === ApplicantStatusEnum::rejected() ? 'required' : ''
            ],
            'processType' => $request->route()->getName(),
            'dataUpdate' => [
                'approval_status' => $request->approval_status,
                'approval_note' => $request->approval_status === ApplicantStatusEnum::rejected() ? $request->approval_note : ''
            ],
        ];

        if ($request->route()->named('verification')) {
            $changeStatusParam['param'] = [
                'verification_status' => 'required|string',
                'note' => $request->verification_status === ApplicantStatusEnum::rejected() ? 'required' : ''
            ];
            $changeStatusParam['dataUpdate'] = [
                'verification_status' => $request->verification_status,
                'note' => $request->verification_status === ApplicantStatusEnum::rejected() ? $request->note : ''
            ];
        }

        return $changeStatusParam;
    }

    public function stockCheking(Request $request)
    {
        $param = [
            'applicant_id' => 'required|numeric',
            'stock_checking_status' => 'required|string'
        ];
        $applicant = (Validation::validate($request, $param)) ? $this->updateApplicant($request) : null;
        return response()->format(Response::HTTP_OK, 'success', $applicant);
    }

    public function masterFaskesCheck($request)
    {
        return $request = (!MasterFaskes::find($request->master_faskes_id)) ? $this->alloableAgencyType($request) : $request;
    }

    public function alloableAgencyType(MasterFaskesRequest $request)
    {
        $masterFaskes = MasterFaskes::createFaskes($request);
        $request['master_faskes_id'] = $masterFaskes->id;
        $response = $request;
        return $response;
    }

    public function uploadLetter(UploadLetterRequest $request, $id)
    {
        $applicant = Applicant::where('id', $request->applicant_id)->where('agency_id', $request->agency_id)->firstOrFail();
        $response = FileUpload::storeLetterFile($request);
        Validation::setCompleteness($request);
        Log::channel('dblogging')->debug('post:v1/logistic-request/letter/' . $id, $request->all());
        return $response;
    }

    public function uploadApplicantFile(UploadApplicantFileRequest $request, $id)
    {

        $request->merge(['applicant_id' => $id]);
        $response = FileUpload::storeApplicantFile($request);
        $applicant = Applicant::where('id', '=', $request->applicant_id)->update(['file' => $response->id]);
        Validation::setCompleteness($request);

        Log::channel('dblogging')->debug('post:v1/logistic-request/identity/' . $id, $request->all());
        return $response;
    }

    public function urgencyChange(LogisticRequestUrgencyChangeRequest $request)
    {

        $model = Applicant::where('id', $request->applicant_id)->where('agency_id', $request->agency_id)->first();
        $model->is_urgency = $request->is_urgency;
        $model->save();
        $response = response()->format(Response::HTTP_OK, 'success', $model);
        Validation::setCompleteness($request);
        Log::channel('dblogging')->debug('post:v1/logistic-request/urgency', $request->all());
        return $response;
    }
}
