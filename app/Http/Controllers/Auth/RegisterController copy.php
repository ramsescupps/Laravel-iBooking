<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendUserJob;
use App\Models\RoleUser;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function register(Request $request)
    {
        $validate = $this->validator($request->all());

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => view('Frontend::components.alert', ['type' => 'danger', 'message' => $validate->errors()->first()])->render()
            ]);
        }

        $agree = $request->post('agree_field', '');

        if (empty($agree)) {
            return response()->json([
                'status' => false,
                'message' => view('Frontend::components.alert', ['type' => 'danger', 'message' => __('Please agree with our terms and conditions')])->render()
            ]);
        }

        event(new Registered($user = $this->create($request->all())));

        $this->guard()->login($user);

        if ($response = $this->registered($request, $user)) {
            return $response;
        }

        RoleUser::create([
            'role_id' => 3,
            'user_id' => $user['id']
        ]);

        // $user->user_id = $user['id'];
        // dispatch(new SendUserJob($user));

        return response()->json([
            'status' => true,
            'message' => view('Frontend::components.alert', ['type' => 'success', 'message' => __('Register successfully')])->render(),
            'redirect' => url('/')
        ]);
    }
}
