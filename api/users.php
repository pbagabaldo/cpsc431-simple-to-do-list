<?php
include_once '../config/database.php';

header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"));
$operation = $data->operation ?? $_GET['operation'] ?? '';

// Handles user registration
function register($data, $conn) {
    if (!empty($data->username) && !empty($data->email) && !empty($data->password)) {
        $query = "INSERT INTO Users (Username, Email, Password_Hash) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
        $stmt->execute([$data->username, $data->email, $password_hash]);

        if ($stmt->rowCount()) {
            http_response_code(201);
            echo json_encode(["message" => "User was successfully registered."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Unable to register the user."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Data is incomplete."]);
    }
}

// Handles login
function login($data, $conn) {
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

// Main request handling
if (function_exists($operation)) {
    $operation($data, $conn);
} else {
    http_response_code(400);
    echo json_encode(["message" => "Invalid operation requested."]);
}
