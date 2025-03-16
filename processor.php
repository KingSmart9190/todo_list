<?php
// Enable error reporting for debugging during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('connection.php'); // Ensure this file establishes a valid connection to your database

function generateOTP() {
    return sprintf("%06d", mt_rand(0, 999999)); // Generate a 6-digit random OTP
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'C:/xampp/htdocs/Todo/PHPMailer-6.9.3/src/Exception.php';
require 'C:/xampp/htdocs/Todo/PHPMailer-6.9.3/src/PHPMailer.php';
require 'C:/xampp/htdocs/Todo/PHPMailer-6.9.3/src/SMTP.php';

function sendVerificationEmail($email, $otp) {
    $mail = new PHPMailer(true);

    try {
        // Configure SMTP for Mailtrap
        $mail->isSMTP();
        $mail->Host = 'sandbox.smtp.mailtrap.io'; // Use Mailtrap's SMTP host
        $mail->SMTPAuth = true;
        $mail->Username = 'f4b621e0683508'; // Replace with your Mailtrap username
        $mail->Password = '2a35bda9454096'; // Replace with your Mailtrap password
        $mail->Port = 2525; // Use the SMTP port provided by Mailtrap
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Encryption (STARTTLS)

        // Sender and recipient settings
        $mail->setFrom('noreply@todoapp.com', 'Todo App'); // Mailtrap allows any sender email
        $mail->addAddress($email); // Add recipient email

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'OTP Verification';
        $mail->Body    = "Your OTP is: $otp";

        $mail->send();
        echo 'OTP has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

function verifyOTP($inputOtp, $generatedOtp) {
    if ($inputOtp === $generatedOtp) {
        header('Location: index.html');
        exit();
    } else {
        echo 'Invalid OTP';
    }
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
        header("Location: verify.html");
        exit();
    } elseif ($action === 'verify') {
        // OTP Verification Process
        $email = isset($_POST['email']) ? trim($_POST['email']) : null;
        $otp = isset($_POST['otp']) ? trim($_POST['otp']) : null;

        if ($email === null || $otp === null) {
            echo "Error: Missing email or OTP!";
            exit();
        }

        $stmt = $conn->prepare("SELECT otp, otp_expiry FROM tbl_register WHERE email = ?");
        if (!$stmt) {
            die("Error: " . $conn->error);
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && $user['otp'] === $otp && strtotime($user['otp_expiry']) > time()) {
            $updateStmt = $conn->prepare("UPDATE tbl_register SET is_verified = TRUE, otp = NULL, otp_expiry = NULL WHERE email = ?");
            if (!$updateStmt) {
                die("Error: " . $conn->error);
            }
            $updateStmt->bind_param('s', $email);
            $updateStmt->execute();

            // Redirect to login page (index.html) after successful verification
            header("Location: index.html");
            exit();
        } else {
            echo "Error: Invalid or expired OTP!";
            exit();
        }
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


            // Check if the password is correct
            if (!password_verify($password, $hashedPassword)) {
                echo "Error: Incorrect password.";
                exit();
            }

            // Redirect to main.html after successful login
            header("Location: main.html");
            exit();
        } else {
            echo "Error: No account found with that email.";
            exit();
        }
    }
}

$conn->close();
?>
