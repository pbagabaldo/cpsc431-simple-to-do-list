<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

// Function to fetch all tasks with sorting
function fetchAllTasks($conn, $listId, $sortOption)
{
    $sortField = 'CreatedDate'; // Updated column name
    $sortOrder = 'ASC';

    switch ($sortOption) {
        case 'created-asc':
            $sortField = 'CreatedDate';
            $sortOrder = 'ASC';
            break;
        case 'created-desc':
            $sortField = 'CreatedDate';
            $sortOrder = 'DESC';
            break;
        case 'checked':
            $sortField = 'Status';
            $sortOrder = 'DESC';
            break;
        case 'unchecked':
            $sortField = 'Status';
            $sortOrder = 'ASC';
            break;
    }

    $stmt = $conn->prepare("SELECT * FROM Tasks WHERE List_ID = ? ORDER BY $sortField $sortOrder");
    $stmt->execute([$listId]);
    return $stmt->fetchAll();
}

// Function to create a task
function createTask($conn, $listId, $name, $description)
{
    $stmt = $conn->prepare("INSERT INTO Tasks (List_ID, Name, Description, CreatedDate) VALUES (?, ?, ?, NOW())"); // Updated column name
    $stmt->execute([$listId, $name, $description]);
}

// Function to update a task
function updateTask($conn, $taskId, $name, $description)
{
    $stmt = $conn->prepare("UPDATE Tasks SET Name = ?, Description = ? WHERE Task_ID = ?");
    $stmt->execute([$name, $description, $taskId]);
}

// Function to delete a task
function deleteTask($conn, $taskId)
{
    $stmt = $conn->prepare("DELETE FROM Tasks WHERE Task_ID = ?");
    $stmt->execute([$taskId]);
}

// Function to update task status
function updateTaskStatus($conn, $taskId, $status)
{
    $stmt = $conn->prepare("UPDATE Tasks SET Status = ? WHERE Task_ID = ?");
    $stmt->execute([$status, $taskId]);
}

// Process the request
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['operation'])) {
    $operation = $input['operation'];
    switch ($operation) {
        case 'fetchAllTasks':
            if (!isset($input['list_id']) || !isset($input['sort_option'])) {
                echo json_encode(['message' => 'List ID and sort option are required']);
                break;
            }
            $listId = $input['list_id'];
            $sortOption = $input['sort_option'];
            $tasks = fetchAllTasks($conn, $listId, $sortOption);
            echo json_encode(['tasks' => $tasks]);
            break;

        case 'createTask':
            if (!isset($input['list_id']) || !isset($input['name'])) {
                echo json_encode(['message' => 'List ID and name are required']);
                break;
            }
            $listId = $input['list_id'];
            $name = $input['name'];
            $description = $input['description'] ?? '';
            try {
                createTask($conn, $listId, $name, $description);
                echo json_encode(['message' => 'Task created successfully.']);
            } catch (Exception $e) {
                echo json_encode(['message' => 'Failed to create task: ' . $e->getMessage()]);
            }
            break;

        case 'updateTask':
            if (!isset($input['task_id']) || !isset($input['name']) || !isset($input['description'])) {
                echo json_encode(['message' => 'Task ID, name, and description are required']);
                break;
            }
            $taskId = $input['task_id'];
            $name = $input['name'];
            $description = $input['description'];
            try {
                updateTask($conn, $taskId, $name, $description);
                echo json_encode(['message' => 'Task updated successfully.']);
            } catch (Exception $e) {
                echo json_encode(['message' => 'Failed to update task: ' . $e->getMessage()]);
            }
            break;

        case 'deleteTask':
            if (!isset($input['task_id'])) {
                echo json_encode(['message' => 'Task ID is required']);
                break;
            }
            $taskId = $input['task_id'];
            try {
                deleteTask($conn, $taskId);
                echo json_encode(['message' => 'Task deleted successfully.']);
            } catch (Exception $e) {
                echo json_encode(['message' => 'Failed to delete task: ' . $e->getMessage()]);
            }
            break;

        case 'updateTaskStatus':
            if (!isset($input['task_id']) || !isset($input['status'])) {
                echo json_encode(['message' => 'Task ID and status are required']);
                break;
            }
            $taskId = $input['task_id'];
            $status = $input['status'];
            try {
                updateTaskStatus($conn, $taskId, $status);
                echo json_encode(['message' => 'Task status updated successfully.']);
            } catch (Exception $e) {
                echo json_encode(['message' => 'Failed to update task status: ' . $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['message' => 'Invalid operation']);
            break;
    }
} else {
    echo json_encode(['message' => 'No operation specified']);
}
?>