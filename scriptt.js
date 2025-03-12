document.addEventListener("DOMContentLoaded", () => {
    const createReminderButton = document.getElementById("createReminderButton");
    const todoList = document.getElementById("todoList");
    const completedList = document.getElementById("completedList");

    // Function to initialize existing tasks
    function initializeTasks() {
        const allTasks = document.querySelectorAll("#todoList li, #completedList li");
        allTasks.forEach(task => {
            const statusCircle = task.querySelector(".status-circle");
            if (statusCircle) {
                // Add click event to toggle completion
                statusCircle.addEventListener("click", () => {
                    if (!statusCircle.classList.contains("completed")) {
                        statusCircle.classList.add("completed");
                        completedList.appendChild(task);
                    } else {
                        statusCircle.classList.remove("completed");
                        todoList.appendChild(task);
                    }
                });
            }
        });
    }

    // Function to create a new task
    createReminderButton.addEventListener("click", () => {
        const taskTitle = prompt("Enter task title:");
        const taskDetails = prompt("Enter task details (e.g., Date | Category):");

        if (taskTitle && taskDetails) {
            const newTask = createTaskElement(taskTitle, taskDetails, "work"); // Default category
            todoList.appendChild(newTask);
        }
    });

    // Helper function to create a task element
    function createTaskElement(title, details, category) {
        const li = document.createElement("li");
        li.classList.add("task-item");

        // Create status circle with category color
        const statusCircle = document.createElement("span");
        statusCircle.classList.add("status-circle", category);
        statusCircle.style.cursor = "pointer"; // To indicate it's clickable

        // Add click event to toggle task completion
        statusCircle.addEventListener("click", () => {
            if (!statusCircle.classList.contains("completed")) {
                statusCircle.classList.add("completed");
                completedList.appendChild(li);
            } else {
                statusCircle.classList.remove("completed");
                todoList.appendChild(li);
            }
        });

        const taskInfo = document.createElement("div");
        taskInfo.classList.add("task-info");

        const taskTitle = document.createElement("p");
        taskTitle.classList.add("task-title");
        taskTitle.textContent = title;

        const taskDetails = document.createElement("p");
        taskDetails.classList.add("task-details");
        taskDetails.textContent = details;

        taskInfo.appendChild(taskTitle);
        taskInfo.appendChild(taskDetails);
        li.appendChild(statusCircle);
        li.appendChild(taskInfo);

        return li;
    }

    // Initialize existing tasks on page load
    initializeTasks();
});
