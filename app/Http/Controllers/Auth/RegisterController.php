<?php

namespace App\Http\Controllers\Auth;
use App\User;
use App\Generalsetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Wallet;
use Auth;
use App\Http\Controllers\Rpc\jsonRPCClient;
use Log;
use Khsing\World\World;

use Illuminate\Http\Request;
function compareByName($a, $b) {
   return strcmp($a['name'], $b["name"]);
}

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
    protected $redirectTo = '/home';

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
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $name = explode('@', $data['email'])[0];
        $codeList = Generalsetting::all();
        $settings = [];
        for($i = 0; $i < count($codeList); $i++) {
          $settings[$codeList[$i]["name"]] = $codeList[$i]["value"];
        }

        $client = new jsonRPCClient('http://'.$settings['rpcuser'].':'.$settings['rpcpassword'].'@'.$settings['rpcip'].':'.$settings['rpcport'].'/');

        $user = User::create([
            'name' => $name,
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'is_student_or_alumni' => $data['is_student_or_alumni'],
            'address1' => $data['address1'],
            'address2' => $data['address2'],
            'city' => $data['city'],
            'state' => $data['city'],
            'country' => $data['country'],
            'post_code' => $data['post_code'],
            'mobile_number' => $data['mobile_number'],
            'school_name' => $data['school_name']
        ]);

        $address = $client->getaccountaddress("$user->id");

        Wallet::create([
            'user_id' => $user->id,
            'address' => $address
        ]);

        return $user;
    }


    public function getCountries(Request $request)
    {
      $countries = World::Countries();
      return $countries;
    }

    public function getStates(Request $request)
    {
      $country_code = $request->input('country_id');
      $country = World::getCountryByCode($country_code);
      $provinces = $country->divisions()->get();
      return $provinces;
    }

    public function getCites(Request $request)
    {
      $country_code = $request->input('country_id');
      $country_code = $request->input('country_id');
      $country = World::getCountryByCode($country_code);
      $division_id = $request->input('division_id');
      $cities = [];
      if (isset($division_id)){
        $cities = World::getCitiesByDivision($division_id);
      } else {
        $cities = World::getCitiesByCountry($country->id);
      }
      return $cities;
    }
}
