<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::post('oauth/access_token', function() {
    $response = Authorizer::issueAccessToken();
    if($response) {
        $response['user'] = Auth::user();
        return Response::json($response);
    }
    return Response::json(Authorizer::issueAccessToken());
});


# Forgot Password Confirmation
Route::get('forgot-password/{userId}/{passwordResetCode}', array('as' => 'forgot-password-confirm', 'uses' => 'UserController@getForgotPasswordConfirm'));
Route::post('forgot-password/{userId}/{passwordResetCode}', 'UserController@postForgotPasswordConfirm');

Route::group(['middleware' => 'oauth:payment-vendor', 'prefix' => 'api-v1'], function () {

    Route::post('payment/pay', 'PaymentController@pay');

});

Route::group(['middleware' => 'oauth:payment-client', 'prefix' => 'api-v1'], function () {

    Route::post('payment/confirm', 'PaymentController@confirm');

});


Route::get('/api-v1/app_vesion', function(){
	$app['version_id'] = 1;
	$app['version_name'] = '1.1';
	$app['updated_at'] ='2015-09-01';
	return response()->json($app);
});





//admin routes

Route::get('/administration',    	'AdminLoginController@getadminlogin');
Route::post('/administration',    	'AdminLoginController@postLogin');

Route::group(['middleware' => 'auth', 'prefix' => 'admin', ], function ()
{
	Route::get('/logout', 				'AdminLoginController@logout');
    Route::get('/dashboard',            'AdminLoginController@dashboard');

    Route::resource('users',            'UsersController');
    Route::post('users/{id}',       	'UsersController@update');
    Route::get('users/{id}/delete',     'UsersController@destroy');

    Route::resource('competition-question',             'CompetitionQuestionController');
    Route::post('competition-question/{id}/update',     'CompetitionQuestionController@update');
    Route::get('competition-question/{id}/delete',      'CompetitionQuestionController@destroy');

    Route::resource('competition-answers',              'CompetitionAnswerController');
    Route::get('competition-answers-correct/{id}',      'CompetitionAnswerController@correct');
    Route::get('competition-answers-uncorrect/{id}',    'CompetitionAnswerController@uncorrect');
    Route::resource('group-users',    		            'GroupUserController');

});
