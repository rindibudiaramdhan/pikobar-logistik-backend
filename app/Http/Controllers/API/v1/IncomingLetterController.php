<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\IncomingLetter;

class IncomingLetterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = IncomingLetter::getIncomingLetterList($request);
        return response()->format(200, 'success', $data);
    }
    
    /**
     * Display a listing of the resource.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $data = IncomingLetter::showIncomingLetterDetail($request, $id);
        return response()->format(200, 'success', $data);
    }
}
