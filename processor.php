<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('connection.php'); // Ensure this file establishes a valid connection to your database

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'register') {
        // Registration Process
        $email = isset($_POST['signup-email']) ? strtolower(trim($_POST['signup-email'])) : null;
        $password = isset($_POST['signup-password']) ? $_POST['signup-password'] : null;
        $confirmpassword = isset($_POST['confirm-password']) ? $_POST['confirm-password'] : null;

        if ($email === null || $password === null || $confirmpassword === null) {
            echo "Error: Missing email or password!";
            exit();
        }

        if ($password !== $confirmpassword) {
            echo "Error: Passwords do not match!";
            exit();
        }

        $stmt = $conn->prepare("SELECT email FROM tbl_register WHERE email = ?");
        if (!$stmt) {
            die("Error: " . $conn->error);
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "Error: Email already registered!";
            exit();
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO tbl_register (email, password) VALUES (?, ?)");
        if (!$stmt) {
            die("Error: " . $conn->error);
        }
        $stmt->bind_param('ss', $email, $hashedPassword);
        $stmt->execute();
        echo "Registration successful!";
        exit();
    } elseif ($action === 'login') {
        // Login Process
        $email = isset($_POST['login-email']) ? trim($_POST['login-email']) : null;
        $password = isset($_POST['login-password']) ? $_POST['login-password'] : null;

        if ($email === null || $password === null) {
            echo "Error: Missing email or password!";
            exit();
        }

        $stmt = $conn->prepare("SELECT password FROM tbl_register WHERE email = ?");
        if (!$stmt) {
            die("Error: " . $conn->error);
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($hashedPassword);
            $stmt->fetch();

            // Debugging statements
            echo "Entered password: " . htmlspecialchars($password) . "<br>";
            echo "Hashed password from database: " . htmlspecialchars($hashedPassword) . "<br>";
            echo "Length of hashed password: " . strlen($hashedPassword) . "<br>";

            if (password_verify($password, $hashedPassword)) {
                echo "Password verification successful.<br>";
                header("Location: index2.html");
                exit();
            } else {
                echo "Error: Incorrect password.";
            }
        } else {
            echo "Error: No account found with that email.";
        }
        $stmt->close();
    }
}

$conn->close();
?>