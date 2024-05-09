<?php
session_start();
include_once '../config/database.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$data = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid JSON format"]);
    exit;
}

$operation = $data['operation'] ?? '';

// Utility function to send JSON responses
function sendJson($status, $message, $additional = [])
{
    http_response_code($status);
    echo json_encode(array_merge(["message" => $message], $additional));
}

// Function to create a new task list
function createTaskList($data, $conn)
{
    if (!empty($data['title'])) {
        $query = "INSERT INTO TaskLists (User_ID, Title) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $data['title']]);
        if ($stmt->rowCount()) {
            sendJson(201, "Task list created successfully.", ["List_ID" => $conn->lastInsertId()]);
        } else {
            sendJson(503, "Unable to create task list.");
        }
    } else {
        sendJson(400, "Title is required.");
    }
}

function deleteTaskList($data, $conn)
{
    if (!empty($data['list_id'])) {
        $query = "DELETE FROM TaskLists WHERE List_ID = ? AND User_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data['list_id'], $_SESSION['user_id']]);
        if ($stmt->rowCount()) {
            sendJson(200, "Task list deleted successfully.");
        } else {
            sendJson(404, "No task list found with the given ID, or you do not have permission to delete it.");
        }
    } else {
        sendJson(400, "List ID is missing.");
    }
}

function updateTaskList($data, $conn)
{
    if (!empty($data['list_id']) && !empty($data['title'])) {
        $query = "UPDATE TaskLists SET Title = ? WHERE List_ID = ? AND User_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data['title'], $data['list_id'], $_SESSION['user_id']]);
        if ($stmt->rowCount()) {
            sendJson(200, "Task list updated successfully.");
        } else {
            sendJson(404, "No task list found with the given ID, or you do not have permission to update it.");
        }
    } else {
        sendJson(400, "Data is incomplete or missing List ID.");
    }
}

function fetchAllTaskLists($data, $conn) {
    $query = "SELECT List_ID, Title FROM TaskLists WHERE User_ID = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {
        sendJson(200, "Task lists fetched successfully.", ["task_lists" => $results]);
    } else {
        sendJson(404, "No task lists found.");
    }
}

// Main request handling
if (function_exists($operation)) {
    $operation($data, $conn);
} else {
    http_response_code(400);
    echo json_encode(["message" => "Invalid operation requested."]);
}
