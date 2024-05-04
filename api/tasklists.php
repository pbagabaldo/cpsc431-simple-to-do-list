<?php
include_once '../config/database.php';  

header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"));
$operation = $data->operation ?? $_GET['operation'] ?? '';

// Utility function to send JSON responses
function sendJson($status, $message, $additional = []) {
    http_response_code($status);
    echo json_encode(array_merge(["message" => $message], $additional));
}

// Function to create a new task list
function createTaskList($data, $conn) {
    if (!empty($data->user_id) && !empty($data->title)) {
        $query = "INSERT INTO TaskLists (User_ID, Title) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data->user_id, $data->title]);
        if ($stmt->rowCount()) {
            sendJson(201, "Task list created successfully.", ["List_ID" => $conn->lastInsertId()]);
        } else {
            sendJson(503, "Unable to create task list.");
        }
    } else {
        sendJson(400, "Data is incomplete.");
    }
}

// Function to delete a task list
function deleteTaskList($data, $conn) {
    if (!empty($data->list_id)) {
        $query = "DELETE FROM TaskLists WHERE List_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data->list_id]);
        if ($stmt->rowCount()) {
            sendJson(200, "Task list deleted successfully.");
        } else {
            sendJson(404, "No task list found with the given ID.");
        }
    } else {
        sendJson(400, "List ID is missing.");
    }
}

// Function to update a task list
function updateTaskList($data, $conn) {
    if (!empty($data->list_id) && !empty($data->title)) {
        $query = "UPDATE TaskLists SET Title = ? WHERE List_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data->title, $data->list_id]);
        if ($stmt->rowCount()) {
            sendJson(200, "Task list updated successfully.");
        } else {
            sendJson(404, "No task list found with the given ID.");
        }
    } else {
        sendJson(400, "Data is incomplete or missing List ID.");
    }
}

// Main request handling
if (function_exists($operation)) {
    $operation($data, $conn);
} else {
    http_response_code(400);
    echo json_encode(["message" => "Invalid operation requested."]);
}
