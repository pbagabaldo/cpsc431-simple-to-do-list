let userId; // Global variable to hold the user ID

document.addEventListener("DOMContentLoaded", function () {
    fetchUserId();
});

function fetchUserId() {
    fetch('../api/users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ operation: 'getUserInfo' })
    })
        .then(response => response.json())
        .then(data => {
            if (data.user_id) {
                userId = data.user_id;  // Store user ID globally
                console.log("User ID retrieved:", userId);
                loadTaskLists();  // Now load tasks
            } else {
                console.error('Error:', data.message);
                updateErrorMessage('Please log in to view tasks');
            }
        })
        .catch(error => {
            console.error('Error fetching user ID:', error);
            updateErrorMessage('Error fetching user ID');
        });
}

function createTaskList() {
    const title = document.getElementById('createTitle').value.trim();
    if (!userId || !title) {
        alert('User not logged in or title is missing');
        return;
    }
    fetch('../api/taskLists.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ operation: 'createTaskList', title: title, user_id: userId })
    })
    .then(response => response.text()) // Read as text for debugging
    .then(text => {
        try {
            const data = JSON.parse(text);
            alert(data.message);
            if (data.message === "Task list created successfully.") {
                loadTaskLists(); // Reload the list
            }
        } catch (err) {
            console.error('Failed to parse JSON:', err, text);
            updateErrorMessage('Failed to create task list');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        updateErrorMessage('Failed to create task list');
    });
}

function loadTaskLists() {
    if (!userId) {
        console.error('Cannot load task lists; user ID is missing');
        updateErrorMessage('User ID is missing, cannot load task lists');
        return;
    }
    fetch('../api/taskLists.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ operation: 'fetchAllTaskLists' })
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.task_lists) {
                displayTaskLists(data.task_lists);
            } else {
                console.log('No task lists found or invalid data:', data.message);
                updateErrorMessage(data.message || 'No task lists found');
            }
        } catch (err) {
            console.error('Failed to parse JSON:', err, text);
            updateErrorMessage('Failed to load task lists');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        updateErrorMessage('Failed to load task lists');
    });
}

function displayTaskLists(taskLists) {
    const taskListTable = document.getElementById('taskListTable').getElementsByTagName('tbody')[0];
    taskListTable.innerHTML = ''; // Clear previous entries

    taskLists.forEach(list => {
        let row = taskListTable.insertRow();
        let titleCell = row.insertCell(0);
        let link = document.createElement('a');
        link.href = `taskPage.html?list_id=${list.List_ID}&title=${encodeURIComponent(list.Title)}`;
        link.textContent = list.Title;
        titleCell.appendChild(link);

        let actionsCell = row.insertCell(1);
        let actionDiv = document.createElement('div');
        actionDiv.className = 'action-buttons';
        actionDiv.appendChild(createButton("Update", () => updateTaskList(list.List_ID)));
        actionDiv.appendChild(createButton("Delete", () => deleteTaskList(list.List_ID)));
        actionsCell.appendChild(actionDiv);
    });
}

function sortLists() {
    const sorter = document.getElementById('listSorter').value;
    let [sortField, sortOrder] = sorter.split('-');

    if (sortField === 'name') {
        sortField = 'Title';
    } else if (sortField === 'created') {
        sortField = 'Created';
    }

    fetch('../api/taskLists.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ operation: 'fetchAllTaskLists', sortField: sortField, sortOrder: sortOrder.toUpperCase() })
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.task_lists) {
                displayTaskLists(data.task_lists);
            } else {
                console.log('No task lists found or invalid data:', data.message);
            }
        } catch (err) {
            console.error('Failed to parse JSON:', err, text);
        }
    })
    .catch(error => console.error('Error fetching sorted lists:', error));
}

function updateTaskList(listId) {
    const newTitle = prompt("Enter the new title for the task list:");
    if (newTitle && newTitle.trim()) {
        fetch('../api/taskLists.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ operation: 'updateTaskList', list_id: listId, title: newTitle.trim() })
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.message === "Task list updated successfully.") {
                    loadTaskLists(); // Reload the list
                }
            })
            .catch(error => {
                console.error('Error:', error);
                updateErrorMessage('Failed to update task list');
            });
    }
}

function deleteTaskList(listId) {
    if (confirm("Are you sure you want to delete this task list?")) {
        fetch('../api/taskLists.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ operation: 'deleteTaskList', list_id: listId })
        })
        .then(response => response.text()) // Read as text for debugging
        .then(text => {
            try {
                const data = JSON.parse(text);
                alert(data.message);
                if (data.message === "Task list deleted successfully.") {
                    loadTaskLists(); // Reload the list
                }
            } catch (err) {
                console.error('Failed to parse JSON:', err, text);
                updateErrorMessage('Failed to delete task list');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            updateErrorMessage('Failed to delete task list');
        });
    }
}

function updateErrorMessage(message) {
    const errorMessageElement = document.getElementById('error-message');
    if (errorMessageElement) {
        errorMessageElement.innerText = message;
    } else {
        console.log('Error message element not found in the DOM');
    }
}

function createButton(text, onclickFunction) {
    const button = document.createElement('button');
    button.textContent = text;
    button.onclick = onclickFunction;
    return button;
}

function logOut() {
    localStorage.removeItem('authToken');
    window.location.href = 'index.html'; // Redirect to the login page
}
