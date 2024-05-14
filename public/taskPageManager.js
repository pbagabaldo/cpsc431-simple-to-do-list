let listId, listTitle;

document.addEventListener("DOMContentLoaded", () => {
  try {
    const urlParams = new URLSearchParams(window.location.search);
    listId = urlParams.get("list_id");
    listTitle = urlParams.get("title");
    console.log("URL Params:", { list_id: listId, title: listTitle });

    if (listId && listTitle) {
      document.getElementById("taskListTitle").innerText = `Tasks for ${decodeURIComponent(listTitle.replace(/\+/g, " "))}`;

      // Retrieve the stored sort option
      const storedSortOption = localStorage.getItem('sortOption') || 'created-asc';
      document.getElementById('taskSorter').value = storedSortOption;
      loadTasks(storedSortOption);
    } else {
      throw new Error("Invalid task list ID or title.");
    }
  } catch (error) {
    console.error("Error parsing URL parameters:", error);
    updateErrorMessage("Error parsing URL parameters.");
  }
});

function cleanResponseText(text) {
  console.log("Raw Response Text:", text);
  return text.replace(/^[^({\[]*|[^)}\]]*$/g, "");
}

function loadTasks(sortOption = 'created-asc') {
  const payload = { operation: "fetchAllTasks", list_id: listId, sort_option: sortOption };
  console.log("Sending payload:", payload);

  fetch("../api/tasks.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  })
    .then((response) => response.text())
    .then((text) => {
      const cleanedText = cleanResponseText(text);
      try {
        const data = JSON.parse(cleanedText);
        populateTaskTable(data.tasks);
      } catch (error) {
        console.error("JSON Parse Error:", error, "Cleaned Text:", cleanedText);
        updateErrorMessage("Failed to parse JSON response");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      updateErrorMessage("Failed to load tasks");
    });
}

function populateTaskTable(tasks) {
  const taskTable = document.getElementById("taskTable").getElementsByTagName("tbody")[0];
  taskTable.innerHTML = "";

  if (Array.isArray(tasks)) {
    tasks.forEach((task) => {
      const row = taskTable.insertRow();

      const statusCell = row.insertCell(0);
      const statusCheckbox = document.createElement("input");
      statusCheckbox.type = "checkbox";
      statusCheckbox.checked = task.Status === 1;
      statusCheckbox.addEventListener("change", () => updateTaskStatus(task.Task_ID, statusCheckbox.checked));
      statusCell.appendChild(statusCheckbox);
      console.log("Status cell inserted:", statusCheckbox.checked);

      const nameCell = row.insertCell(1);
      nameCell.innerText = task.Name;
      console.log("Name cell inserted:", task.Name);

      const descriptionCell = row.insertCell(2);
      descriptionCell.innerText = task.Description;
      console.log("Description cell inserted:", task.Description);

      const actionsCell = row.insertCell(3);
      const actionDiv = document.createElement('div');
      actionDiv.className = 'action-buttons';
      actionDiv.appendChild(createButton("Update", () => updateTask(task.Task_ID)));
      actionDiv.appendChild(createButton("Delete", () => deleteTask(task.Task_ID)));
      actionsCell.appendChild(actionDiv);
      console.log("Actions cell inserted for task:", task.Task_ID);

      console.log("Row HTML:", row.innerHTML);
    });
  } else {
    updateErrorMessage("No tasks found");
  }
}

function createTask() {
  const name = document.getElementById("taskName").value.trim();
  const description = document.getElementById("taskDescription").value.trim();
  if (!listId || !name) {
    alert("Task list ID or task name is missing");
    return;
  }
  const payload = { operation: "createTask", list_id: listId, name, description };
  console.log("Sending payload:", payload);

  fetch("../api/tasks.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  })
    .then((response) => response.text())
    .then((text) => {
      const cleanedText = cleanResponseText(text);
      try {
        const data = JSON.parse(cleanedText);
        alert(data.message);
        if (data.message === "Task created successfully.") {
          // Retrieve the current sort option and reload tasks with sorting
          const sortOption = localStorage.getItem('sortOption') || 'created-asc';
          loadTasks(sortOption);
        }
      } catch (error) {
        console.error("JSON Parse Error:", error, "Cleaned Text:", cleanedText);
        updateErrorMessage("Failed to parse JSON response");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      updateErrorMessage("Failed to create task");
    });
}

function updateTask(taskId) {
  const newName = prompt("Enter the new name for the task:");
  const newDescription = prompt("Enter the new description for the task:");
  if (newName && newDescription) {
    const payload = { operation: "updateTask", task_id: taskId, name: newName.trim(), description: newDescription.trim() };
    console.log("Sending payload:", payload);

    fetch("../api/tasks.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
      .then((response) => response.text())
      .then((text) => {
        const cleanedText = cleanResponseText(text);
        try {
          const data = JSON.parse(cleanedText);
          alert(data.message);
          if (data.message === "Task updated successfully.") {
            const sortOption = localStorage.getItem('sortOption') || 'created-asc';
            loadTasks(sortOption);
          }
        } catch (error) {
          console.error("JSON Parse Error:", error, "Cleaned Text:", cleanedText);
          updateErrorMessage("Failed to parse JSON response");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        updateErrorMessage("Failed to update task");
      });
  }
}

function deleteTask(taskId) {
  if (confirm("Are you sure you want to delete this task?")) {
    const payload = { operation: "deleteTask", task_id: taskId };
    console.log("Sending payload:", payload);

    fetch("../api/tasks.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
      .then((response) => response.text())
      .then((text) => {
        const cleanedText = cleanResponseText(text);
        try {
          const data = JSON.parse(cleanedText);
          alert(data.message);
          if (data.message === "Task deleted successfully.") {
            const sortOption = localStorage.getItem('sortOption') || 'created-asc';
            loadTasks(sortOption);
          }
        } catch (error) {
          console.error("JSON Parse Error:", error, "Cleaned Text:", cleanedText);
          updateErrorMessage("Failed to parse JSON response");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        updateErrorMessage("Failed to delete task");
      });
  }
}

function updateTaskStatus(taskId, status) {
  const payload = { operation: "updateTaskStatus", task_id: taskId, status: status ? 1 : 0 };
  console.log("Sending payload:", payload, "Task ID:", taskId);

  fetch("../api/tasks.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  })
    .then((response) => response.text())
    .then((text) => {
      const cleanedText = cleanResponseText(text);
      try {
        const data = JSON.parse(cleanedText);
        alert(data.message);
        if (data.message === "Task status updated successfully.") {
          // Retrieve the current sort option and reload tasks with sorting
          const sortOption = localStorage.getItem('sortOption') || 'created-asc';
          loadTasks(sortOption);
        }
      } catch (error) {
        console.error("JSON Parse Error:", error, "Cleaned Text:", cleanedText);
        updateErrorMessage("Failed to parse JSON response");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      updateErrorMessage("Failed to update task status");
    });
}

function updateErrorMessage(message) {
  const errorMessageElement = document.getElementById("error-message");
  if (errorMessageElement) {
    errorMessageElement.innerText = message;
  } else {
    console.log("Error message element not found in the DOM");
  }
}

function createButton(text, onclickFunction) {
  const button = document.createElement("button");
  button.textContent = text;
  button.onclick = onclickFunction;
  return button;
}

function logOut() {
  localStorage.removeItem('authToken');
  window.location.href = 'index.html';
}

function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    window.location.href = 'index.html'; // Provide a fallback home page or directory
  }
}

function sortTasks() {
  let sortOption = document.getElementById('taskSorter').value;
  localStorage.setItem('sortOption', sortOption); // Store the selected sort option
  loadTasks(sortOption);
}
