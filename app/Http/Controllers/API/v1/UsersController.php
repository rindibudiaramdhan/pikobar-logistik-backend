<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Requests\UserRegisterRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UsersController extends ApiController
{
    public function __construct()
    {
        $this->middleware('jwt-auth', ['except' => ['authenticate', 'register']]);
    }

    public function authenticate(Request $request)
    {
        // grab credentials from the request
        $credentials = $request->only('username', 'password');
        try {
            // attempt to verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->format(Response::HTTP_UNAUTHORIZED, 'invalid_credentials');
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->format(Response::HTTP_INTERNAL_SERVER_ERROR, 'could_not_create_token');
        }
        $user = JWTAuth::user();
        $status = 'success';
        // all good so return the token
        return response()->format(Response::HTTP_OK, true, compact('token', 'user'));
    }

    public function register(UserRegisterRequest $request)
    {
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'name' => $request->name,
            'password' => bcrypt($request->password),
            'roles' => $request->roles,
            'agency_name' => $request->agency_name,
            'code_district_city' => $request->code_district_city,
            'name_district_city' => $request->name_district_city,
            'phase' => $request->phase,
        ]);
        $response = response()->format(Response::HTTP_OK, true, [
            'token' => JWTAuth::fromUser($user),
            'user' => $user,
        ]);
        return $response;
    }

    public function me(Request $request)
    {
        $currentUser = JWTAuth::user();
        return response()->format(Response::HTTP_OK, true, $currentUser);
    }

    public function changePassword(Request $request)
    {
        $request->request->add(['username' => JWTAuth::user()->username]);
        $response = $this->authenticate($request);
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $update = User::where('id', JWTAuth::user()->id)->update(['password' => bcrypt($request->password_new)]);
            return response()->format(Response::HTTP_OK, true, $update);
        }
        return $response;
    }
}
