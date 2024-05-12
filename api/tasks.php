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

// Function to create a new task
function createTask($data, $conn) {
    if (!empty($data->list_id) && !empty($data->name)) {
        $query = "INSERT INTO Tasks (List_ID, Name, Description, Status) VALUES (?, ?, ?, ?)";
        $status = isset($data->status) ? $data->status : 0;
        $stmt = $conn->prepare($query);
        $stmt->execute([$data->list_id, $data->name, $data->description ?? '', $status]);
        if ($stmt->rowCount()) {
            sendJson(201, "Task created successfully.", ["Task_ID" => $conn->lastInsertId()]);
        } else {
            sendJson(503, "Unable to create task.");
        }
    } else {
        sendJson(400, "Data is incomplete.");
    }
}

// Function to update a task
function updateTask($data, $conn) {
    if (!empty($data->task_id) && !empty($data->name)) {
        $query = "UPDATE Tasks SET Name = ?, Description = ?, Status = ? WHERE Task_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data->name, $data->description ?? '', $data->status ?? 0, $data->task_id]);
        if ($stmt->rowCount()) {
            sendJson(200, "Task updated successfully.");
        } else {
            sendJson(404, "No task found with the given ID.");
        }
    } else {
        sendJson(400, "Data is incomplete or missing Task ID.");
    }
}

// Function to update task status
function updateTaskStatus($data, $conn) {
    if (!empty($data->task_id) && isset($data->status)) {
        $query = "UPDATE Tasks SET Status = ? WHERE Task_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data->status, $data->task_id]);
        if ($stmt->rowCount()) {
            sendJson(200, "Task status updated successfully.");
        } else {
            sendJson(404, "No task found with the given ID.");
        }
    } else {
        sendJson(400, "Data is incomplete or missing Task ID/Status.");
    }
}

// Function to delete a task
function deleteTask($data, $conn) {
    if (!empty($data->task_id)) {
        $query = "DELETE FROM Tasks WHERE Task_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data->task_id]);
        if ($stmt->rowCount()) {
            sendJson(200, "Task deleted successfully.");
        } else {
            sendJson(404, "No task found with the given ID.");
        }
    } else {
        sendJson(400, "Task ID is missing.");
    }
}

// Function to fetch all tasks for a specific task list
function fetchAllTasks($data, $conn) {
    if (!empty($data->list_id)) {
        $query = "SELECT * FROM Tasks WHERE List_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data->list_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($results) {
            sendJson(200, "Tasks fetched successfully.", ["tasks" => $results]);
        } else {
            sendJson(404, "No tasks found for the given list ID.");
        }
    } else {
        sendJson(400, "List ID is missing.");
    }
}

// Main request handling
if (function_exists($operation)) {
    $operation($data, $conn);
} else {
    http_response_code(400);
    echo json_encode(["message" => "Invalid operation requested."]);
}
