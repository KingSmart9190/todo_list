<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('connection.php'); // Ensure this file establishes a valid connection to your database

function generateOTP() {
    return sprintf("%06d", mt_rand(0, 999999));
}

function sendVerificationEmail($email, $otp) {
    $to = $email;
    $subject = "Email Verification";
    $message = "Your OTP for verification is: " . $otp;
    $headers = "From: noreply@todoapp.com";
    
    mail($to, $subject, $message, $headers);
}

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
        $otp = generateOTP();
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $stmt = $conn->prepare("INSERT INTO tbl_register (email, password, otp, otp_expiry) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            die("Error: " . $conn->error);
        }
        $stmt->bind_param('ssss', $email, $hashedPassword, $otp, $otp_expiry);
        $stmt->execute();

        sendVerificationEmail($email, $otp);

        echo "Registration successful! Please check your email for verification code.";
        exit();
    } elseif ($action === 'verify') {
        $email = $_POST['email'];
        $otp = $_POST['otp'];

        $stmt = $conn->prepare("SELECT otp, otp_expiry FROM tbl_register WHERE email = ?");
        if (!$stmt) {
            die("Error: " . $conn->error);
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && $user['otp'] === $otp && strtotime($user['otp_expiry']) > time()) {
            $updateStmt = $conn->prepare("UPDATE tbl_register SET is_verified = TRUE WHERE email = ?");
            if (!$updateStmt) {
                die("Error: " . $conn->error);
            }
            $updateStmt->bind_param('s', $email);
            $updateStmt->execute();
            echo "Email verified successfully!";
        } else {
            echo "Invalid or expired OTP";
        }
        exit();
    } elseif ($action === 'login') {
        // Login Process
        $email = isset($_POST['login-email']) ? trim($_POST['login-email']) : null;
        $password = isset($_POST['login-password']) ? $_POST['login-password'] : null;

        if ($email === null || $password === null) {
            echo "Error: Missing email or password!";
            exit();
        }

        $stmt = $conn->prepare("SELECT password, is_verified FROM tbl_register WHERE email = ?");
        if (!$stmt) {
            die("Error: " . $conn->error);
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($hashedPassword, $isVerified);
            $stmt->fetch();

            // Debugging statements
            echo "Entered password: " . htmlspecialchars($password) . "<br>";
            echo "Hashed password from database: " . htmlspecialchars($hashedPassword) . "<br>";
            echo "Length of hashed password: " . strlen($hashedPassword) . "<br>";

            if (!$isVerified) {
                echo "Please verify your email first";
                exit();
            }

            if (password_verify($password, $hashedPassword)) {
                echo "Password verification successful.<br>";
                header("Location: main.html");
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