<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require '../includes/DbOperations.php';

$app = new \Slim\App;

/*
endpoint: createuser
parameters: email,password,name,school
method: POST
*/

$app->post('/createuser', function(Request $request,Response $response){
    if(haveEmptyParameters(array('email','password','name','school'))){
        $request_data = $request->getParsedBody();

        $email = $request_data['email'];
        $password = $request_data['password'];
        $name = $request_data['name'];
        $school = $request_data['school'];

        $hash_password = password_hash($password, PASSWORD_DEFAULT);

        $db = new DbOperations;

        $result = $dn->createUser($email,$hash_password,$name,$school);

        if($result == USER_CREATED){

        }else if($result == USER_FAILURE) {

        }else if($result == USER_EXISTS) {

        }
    }
});

function haveEmptyParameters($required_params,$response){
    $error = false;
    $error_params= '';
    $request_params = $_REQUEST;

    foreach ($request_params as $param) {
        if(!isset($request_params[$param]) || strlen($request_params[$param])<= 0){
            $error = true;
            $error_params .= $param. ' ,';
        }
    }

    if($error){
        $error_detail = array();
        $error_detail['error'] = true;
        $error_detail['message'] = 'Required parameters '. substr($error_params, 0 , -2). ' are missing or empty';
        $response->write(json_encode($error_detail));
    }

    return $error;

    }

$app->run();