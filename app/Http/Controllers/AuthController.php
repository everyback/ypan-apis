<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Events\LoginEvent;
use Jenssegers\Agent\Agent;

class AuthController extends Controller
{
    //

    public function __construct()
    {

        $this->middleware('auth:api', ['except' => ['login','refresh']]);
    }


    public function login(Request $request)
    {
        $credentials  = [];
        if ($login = $request->input('name'))
            $credentials['name'] = $login;
        elseif ($login = $request->input('email'))
            $credentials['email'] = $login;
/*        elseif($login = $request->input('phonenumber'))
            $credentials['phonenumber'] = $login;*/
        else
            return response()->json(['error'=>'Bad Request'],400);
        $decode = $request->attributes->get('decode');
        if ($decode['password'])
            $credentials['password'] = $decode['password'];
        else
            return response()->json(['error'=>'Bad Request'],400);
       // echo $sdgdfgdfgfh;
        if (! $token = auth('api')->attempt($credentials))
        {
           event(new LoginEvent(isset($credentials['email']) ? ['email'=>$login]:['name'=>$login], new Agent(), \Request::getClientIp(),'login','0', time()));
            return response()->json(['error' => 'Unauthorized'], 401);
        }
       // dd(auth('api')->user()->id);
        event(new LoginEvent(auth('api')->user(), new Agent(), \Request::getClientIp(),'login','1', time()));
       return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        event(new LoginEvent(auth('api')->user(), new Agent(), \Request::getClientIp(),'logout','1', time()));
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     * 刷新token，如果开启黑名单，以前的token便会失效。
     * 值得注意的是用上面的getToken再获取一次Token并不算做刷新，两次获得的Token是并行的，即两个都可用。
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

}
