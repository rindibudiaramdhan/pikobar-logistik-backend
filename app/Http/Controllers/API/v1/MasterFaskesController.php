<?php

namespace App\Http\Controllers\API\v1;

use App\MasterFaskes;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMasterFaskesRequest;
use App\Http\Requests\VerifyMasterFaskesRequest;
use Illuminate\Support\Facades\Storage;

class MasterFaskesController extends Controller
{
    public function index(Request $request)
    {
        $data = MasterFaskes::getFaskesList($request);
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    public function show($id)
    {
        $data =  MasterFaskes::findOrFail($id);
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    public function store(StoreMasterFaskesRequest $request)
    {
        $model = new MasterFaskes();

        $model->fill($request->input());
        $model->verification_status = 'not_verified';
        $model->is_imported = 0;
        $model->permit_file = $this->permitLetterStore($request);
        $model->save();
        $response = response()->format(Response::HTTP_OK, 'success', $model);

        return $response;
    }

    public function verify(VerifyMasterFaskesRequest $request, $id)
    {
        if ($request->verification_status == 'verified' || $request->verification_status == 'rejected') {
            $model =  MasterFaskes::findOrFail($id);
            $model->verification_status = $request->verification_status;
            $model->save();
            $response = response()->format(Response::HTTP_OK, 'success', $model);
        }
        return $response;
    }

    public function permitLetterStore($request)
    {
        $path = null;
        if ($request->hasFile('permit_file')) {
            $path = Storage::disk('s3')->put('registration/letter', $request->permit_file);
        }
        return $path;
    }
}
