<?php
session_start();
include_once '../config/database.php';

header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"));
$operation = $data->operation ?? $_GET['operation'] ?? '';

// Utility function to send JSON responses
function sendJson($status, $message, $additional = []) {
    http_response_code($status);
    echo json_encode(array_merge(["message" => $message], $additional));
}

// Handles user registration
function register($data, $conn) {
    if (!empty($data->username) && !empty($data->email) && !empty($data->password)) {
        $query = "INSERT INTO Users (Username, Email, Password_Hash) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
        $stmt->execute([$data->username, $data->email, $password_hash]);

        if ($stmt->rowCount()) {
            sendJson(201, "User was successfully registered.");
        } else {
            sendJson(503, "Unable to register the user.");
        }
    } else {
        sendJson(400, "Data is incomplete.");
    }
}

// Handles user login
function login($data, $conn) {
    if (!empty($data->username) && !empty($data->password)) {
        $query = "SELECT User_ID, Password_Hash FROM Users WHERE Username = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data->username]);
        $user = $stmt->fetch();

        if ($user && password_verify($data->password, $user['Password_Hash'])) {
            session_regenerate_id(true); // Regenerate session ID to prevent session fixation
            $_SESSION['user_id'] = $user['User_ID'];
            $_SESSION['username'] = $data->username;
            
            sendJson(200, "Successful login.", ["user_id" => $user['User_ID']]);
        } else {
            sendJson(401, "Login failed.");
        }
    } else {
        sendJson(400, "Data is incomplete.");
    }
}

// Handles user logout
function logout() {
    session_destroy();
    sendJson(200, "Logged out successfully.");
}

// Retrieves user info from session
function getUserInfo() {
    if (isset($_SESSION['user_id'])) {
        sendJson(200, "User info retrieved.", ["user_id" => $_SESSION['user_id'], "username" => $_SESSION['username']]);
    } else {
        sendJson(403, "No user ID found.");
    }
}

// Main request handling
if (function_exists($operation)) {
    $operation($data, $conn);
} else {
    http_response_code(400);
    echo json_encode(["message" => "Invalid operation requested."]);
}
