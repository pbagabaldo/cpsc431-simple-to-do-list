<?php
include_once '../config/database.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];
$path = explode('/', trim($_SERVER['PATH_INFO'],'/'));

if ($conn) {
    if ($path[0] == 'users' && $method == 'POST') {
        registerUser($conn);
    } elseif ($path[0] == 'sessions' && $method == 'POST') {
        loginUser($conn);
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Resource not found."]);
    }
} else {
    http_response_code(503);
    echo json_encode(["message" => "Service unavailable. Unable to connect to database."]);
}

function registerUser($conn) {
    $data = json_decode(file_get_contents("php://input"));
    if (!empty($data->username) && !empty($data->email) && !empty($data->password)) {
        $query = "INSERT INTO Users (Username, Email, Password_Hash) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
        $stmt->execute([$data->username, $data->email, $password_hash]);

        if ($stmt->rowCount() > 0) {
            http_response_code(201);
            echo json_encode(["message" => "User was successfully registered."]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Unable to register the user."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Data is incomplete."]);
    }
}

function loginUser($conn) {
    $data = json_decode(file_get_contents("php://input"));
    if (!empty($data->username) && !empty($data->password)) {
        $query = "SELECT User_ID, Password_Hash FROM Users WHERE Username = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data->username]);
        $user = $stmt->fetch();

        if ($user && password_verify($data->password, $user['Password_Hash'])) {
            http_response_code(200);
            echo json_encode(["message" => "Successful login.", "user_id" => $user['User_ID']]);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Login failed."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Data is incomplete."]);
    }
}

