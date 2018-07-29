<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require '../includes/DbOperations.php';

$app = new \Slim\App([
    'settings'=>[
        'displayErrorDetails'=>true
    ]
]);

/*
endpoint: createuser
parameters: email,password,name,school
method: POST
*/

$app->post('/createuser', function(Request $request,Response $response){
    if(haveEmptyParameters(array('email','password','name','school'), $response)){
        $request_data = $request->getParsedBody();

        $email = $request_data['email'];
        $password = $request_data['password'];
        $name = $request_data['name'];
        $school = $request_data['school'];

        $hash_password = password_hash($password, PASSWORD_DEFAULT);

        $db = new DbOperations;

        $result = $dn->createUser($email,$hash_password,$name,$school);

        if($result == USER_CREATED){

            $message = array();
            $message['error'] = false;
            $messsage['message'] = "User created successfully!";

            $response->write(json_encode($message));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(201);

        }else if($result == USER_FAILURE) {

            $message = array();
            $message['error'] = false;
            $messsage['message'] = "An error occured!";

            $response->write(json_encode($message));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(422);

        }else if($result == USER_EXISTS) {

            $message = array();
            $message['error'] = false;
            $messsage['message'] = "User already exists!";

            $response->write(json_encode($message));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(201);

        }
    }
});

$app->post('/userlogin', function(Request $request,Response $response){
    if(!haveEmptyParameters(array('email','password'), $response)){

        $request_data = $request->getParsedBody();

        $email = $request_data['email'];
        $password = $request_data['password'];

        $db = new DbOperations;

        $result = $db->userLogin($email, $password);

        if($result == USER_AUTHENTHICATED){
            $user = $db->getUserByEmail($email);
            $response_data = array();

            $response_data['error'] = false;
            $response_data['message'] = "Login successful!";
            $response_data['user'] = $user;

            $response->write(json_encode($response_data));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(200);

        }else if($result == USER_NOT_FOUND){

            $response_data = array();

            $response_data['error'] = true;
            $response_data['message'] = "User not found!";

            $response->write(json_encode($response_data));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(404);

        }else if($result == USER_PASSWORD_DO_NOT_MATCH){
            $response_data = array();

            $response_data['error'] = true;
            $response_data['message'] = "Password does not match!";

            $response->write(json_encode($response_data));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(200);

        }

    }

    return $response->withHeader("Content-Type","application/json")
            ->withStatus(422);
});

function haveEmptyParameters($required_params,$response){
    $error = false;
    $error_params= '';
    $request_params = $_REQUEST;

    foreach ($required_params as $param) {
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