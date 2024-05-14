<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

// Function to fetch all task lists with sorting
function fetchAllTaskLists($conn, $sortField = 'Created', $sortOrder = 'ASC') {
    $validFields = ['Title', 'Created'];
    if (!in_array($sortField, $validFields)) {
        $sortField = 'Created';
    }

    $validOrders = ['ASC', 'DESC'];
    if (!in_array($sortOrder, $validOrders)) {
        $sortOrder = 'ASC';
    }

    $stmt = $conn->prepare("SELECT * FROM TaskLists ORDER BY $sortField $sortOrder");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Function to create a task list
function createTaskList($conn, $title, $userId) {
    $stmt = $conn->prepare("INSERT INTO TaskLists (Title, Created, User_ID) VALUES (?, NOW(), ?)");
    $stmt->execute([$title, $userId]);
}

// Function to delete tasks by list ID
function deleteTasksByListId($conn, $listId) {
    $stmt = $conn->prepare("DELETE FROM Tasks WHERE List_ID = ?");
    $stmt->execute([$listId]);
}

// Function to delete a task list
function deleteTaskList($conn, $listId) {
    deleteTasksByListId($conn, $listId);
    $stmt = $conn->prepare("DELETE FROM TaskLists WHERE List_ID = ?");
    $stmt->execute([$listId]);
}

// Process the request
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['operation'])) {
    $operation = $input['operation'];
    switch ($operation) {
        case 'fetchAllTaskLists':
            $sortField = $input['sortField'] ?? 'Created';
            $sortOrder = $input['sortOrder'] ?? 'ASC';
            $taskLists = fetchAllTaskLists($conn, $sortField, $sortOrder);
            echo json_encode(['task_lists' => $taskLists]);
            break;

        case 'createTaskList':
            if (!isset($input['title']) || !isset($input['user_id'])) {
                echo json_encode(['message' => 'Title and User ID are required']);
                break;
            }
            $title = $input['title'];
            $userId = $input['user_id'];
            try {
                createTaskList($conn, $title, $userId);
                echo json_encode(['message' => 'Task list created successfully.']);
            } catch (Exception $e) {
                echo json_encode(['message' => 'Failed to create task list: ' . $e->getMessage()]);
            }
            break;

        case 'updateTaskList':
            if (!isset($input['list_id']) || !isset($input['title'])) {
                echo json_encode(['message' => 'List ID and title are required']);
                break;
            }
            $listId = $input['list_id'];
            $title = $input['title'];
            $stmt = $conn->prepare("UPDATE TaskLists SET Title = ? WHERE List_ID = ?");
            $stmt->execute([$title, $listId]);
            echo json_encode(['message' => 'Task list updated successfully.']);
            break;

        case 'deleteTaskList':
            if (!isset($input['list_id'])) {
                echo json_encode(['message' => 'List ID is required']);
                break;
            }
            $listId = $input['list_id'];
            try {
                deleteTaskList($conn, $listId);
                echo json_encode(['message' => 'Task list deleted successfully.']);
            } catch (Exception $e) {
                echo json_encode(['message' => 'Failed to delete task list: ' . $e->getMessage()]);
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
