<?php

use App\Http\Controllers\ImageController;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['return.json'])->group(function () {
    Route::post('register', 'App\Http\Controllers\UserController@register');
    Route::get('images/{id}', [ImageController::class, 'show'])->name('image.show');
    Route::post('login', 'App\Http\Controllers\UserController@login')->name('login');
});


Route::resource('user/edit', 'App\Http\Controllers\UserController');
// Route::resource('/news', 'App\Http\Controllers\NewsController');


Route::group(['middleware' => 'auth:api'], function () {
    Route::get('user/detail', 'App\Http\Controllers\UserController@details');
    Route::post('logout', 'App\Http\Controllers\UserController@logout');
    Route::resource('/siswa', 'App\Http\Controllers\SiswaController');
});

Route::group(['middleware' => ['auth:api', 'role:3']], function() {
    Route::resource('/news', 'App\Http\Controllers\NewsController');
});
// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Forgot Pass
Route::post('/forgot-password', function(Request $request) {
    $request->validate(["email" => 'required|email']);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    return $status === Password::RESET_LINK_SENT ? response()->json(["message" => __($status)], 200) : response()->json(["error" => __($status)], 400);
})->name('password.email');

Route::get('/reset-password/{token}', function ($token) {
    $email = $_GET['email'];
    return view('auth.reset-password', ['token' => $token, 'email' => $email]);
})->name('password.reset');
Route::post('/reset-password', function(Request $request) {
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) use ($request) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->save();

            $user->setRememberToken(Str::random(60));

            event(new PasswordReset($user));
        }
    );

    return $status == Password::PASSWORD_RESET ? view("inforeset", ['status' => __($status)]) : view("inforeset", ['status' => __($status)]);
})->name('password.update');