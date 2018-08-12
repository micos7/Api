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

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "secure"=> false,
    "users" => [
        "user" => "test",
    ]
]));

/*
endpoint: createuser
parameters: email,password,name,school
method: POST
*/

$app->post('/createuser', function(Request $request,Response $response){
    if(!haveEmptyParameters(array('email','password','name','school'), $request, $response)){
        $request_data = $request->getParsedBody();

        $email = $request_data['email'];
        $password = $request_data['password'];
        $name = $request_data['name'];
        $school = $request_data['school'];

        $hash_password = password_hash($password, PASSWORD_DEFAULT);

        $db = new DbOperations;

        $result = $db->createUser($email,$hash_password,$name,$school);

        if($result == USER_CREATED){

            $message = array();
            $message['error'] = false;
            $message['message'] = "User created successfully!";

            $response->write(json_encode($message));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(201);

        }else if($result == USER_FAILURE) {

            $message = array();
            $message['error'] = true;
            $message['message'] = "An error occured!";

            $response->write(json_encode($message));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(422);

        }else if($result == USER_EXISTS) {

            $message = array();
            $message['error'] = true;
            $message['message'] = "User already exists!";

            $response->write(json_encode($message));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(422);

        }
    }
});

$app->post('/userlogin', function(Request $request,Response $response){
    if(!haveEmptyParameters(array('email','password'), $request, $response)){

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

$app->get('/allusers', function(Request $request,Response $response){
    $db = new DbOperations;

    $users = $db->getAllUsers();

    $response_data = array();

    $response_data['error'] = false;
    $response_data['users'] = $users;

    $response->write(json_encode($response_data));

    return $response->withHeader("Content-Type","application/json")
    ->withStatus(200);
});

$app->put('/updateuser/{id}', function(Request $request,Response $response, array $args){
    $id = $args['id'];

    if(!haveEmptyParameters(array('email','name','school'), $request, $response)){
        $request_data = $request->getParsedBody();

        $email = $request_data['email'];
        $name = $request_data['name'];
        $school = $request_data['school'];
        $id = $request_data['id'];

        $db = new DbOperations;

        if($db->updateUser($email,$name,$school,$id)){
            $response_data = array();

            $response_data['error'] = false;
            $response_data['message'] = 'User updated succesfully!';
            $user= $db->getUserByEmail($email);
            $response_data['user'] = $user;

            $response->write(json_encode($response_data));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(200);
        }else{
            $response_data = array();

            $response_data['error'] = true;
            $response_data['message'] = 'Please try again later!';
            $user= $db->getUserByEmail($email);
            $response_data['user'] = $user;

            $response->write(json_encode($response_data));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(200);
        }
        
    }

    return $response->withHeader("Content-Type","application/json")
            ->withStatus(200);
});

$app->put('/updatepassword', function(Request $request,Response $response){

    if(!haveEmptyParameters(array('currentpassword','newpassword','email'), $request, $response)){
        $request_data = $request->getParsedBody();

        $currentpassword = $request_data['currentpassword'];
        $newpassword = $request_data['newpassword'];
        $email = $request_data['email'];

        $db = new DbOperations;

        $result = $db->updatePassword($currentpassword, $newpassword, $email);

        if($result == PASSWORD_CHANGED){
            $response_data = array();
            $response_data['error'] = false;
            $response_data['message'] = 'Password updated';
            $response->write(json_encode($response_data));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(200);

        }else if($result == PASSWORD_DO_NOT_MATCH){
            $response_data = array();
            $response_data['error'] = true;
            $response_data['message'] = 'Password does not match!';
            $response->write(json_encode($response_data));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(200);

        }else if($result == PASSWORD_NOT_CHANGED){
            $response_data = array();
            $response_data['error'] = true;
            $response_data['message'] = 'Try again later!';
            $response->write(json_encode($response_data));

            return $response->withHeader("Content-Type","application/json")
            ->withStatus(200);
        }
    }
    return $response->withHeader("Content-Type","application/json")
            ->withStatus(422);
});

$app->put('/deleteuser{id}', function(Request $request,Response $response, array $args){
    $id = $args['id'];
    $db = new DbOperations;

    if($db->deleteUser($id)){
        $response_data = array();
        $response_data['error'] = false;
        $response_data['message'] = 'User deleted!';
    }else{
        $response_data = array();
        $response_data['error'] = true;
        $response_data['message'] = 'User not deleted!';
        
    }
    $response->write(json_encode($response_data));
    return $response->withHeader("Content-Type","application/json")
            ->withStatus(200);
});



function haveEmptyParameters($required_params, $request, $response){
    $error = false;
    $error_params= '';
    $request_params = $request->getParsedBody();

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