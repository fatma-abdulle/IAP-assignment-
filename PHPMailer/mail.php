<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


require 'vendor/autoload.php';

class EmailService {
    private $mail;
    private $pdo;
    
    public function __construct() {
        // Initialize PHPMailer
        $this->mail = new PHPMailer(true);
        
        // Initialize database connection
        $this->initializeDatabase();
        
        // Configure SMTP settings
        $this->configureSMTP();
    }
    
    
    private function initializeDatabase() {
        try {
            
            $host = 'localhost';
            $dbname = 'ics_database';
            $username = 'root';
            $password = '1234';
            
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            
            $this->createUsersTable();
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    
    private function createUsersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            email_verified BOOLEAN DEFAULT FALSE
        )";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Configure SMTP settings
     */
    private function configureSMTP() {
        try {
            // Server settings
            $this->mail->SMTPDebug = 0; /
            $this->mail->isSMTP();
            $this->mail->Host = 'smtp.gmail.com'; // Fixed Gmail SMTP
            $this->mail->SMTPAuth = true;
            $this->mail->Username = 'fatushabdulle00@gmail.com';
            $this->mail->Password = 'oaad kjlh xeoe brzr'; 
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mail->Port = 465;
            
            // Set default sender
            $this->mail->setFrom('fatushabdulle00@gmail.com', 'ICS 2.2 Academy');
            
        } catch (Exception $e) {
            throw new Exception("SMTP configuration failed: " . $e->getMessage());
        }
    }
    
    /**
     * Validate email address
     * @param string $email
     * @return bool
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate name (basic validation)
     * @param string $name
     * @return bool
     */
    public function validateName($name) {
        return !empty(trim($name)) && strlen(trim($name)) >= 2;
    }
    
    /**
     * Save user to database
     * @param string $name
     * @param string $email
     * @return int User ID
     */
    public function saveUser($name, $email) {
        try {
            $sql = "INSERT INTO users (name, email) VALUES (:name, :email)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => trim($name),
                ':email' => strtolower(trim($email))
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                throw new Exception("Email address already registered");
            }
            throw new Exception("Failed to save user: " . $e->getMessage());
        }
    }
    
    /**
     * Get all users in ascending order (for Part II of assignment)
     * @return array
     */
    public function getAllUsers() {
        try {
            $sql = "SELECT id, name, email, created_at FROM users ORDER BY created_at ASC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            throw new Exception("Failed to retrieve users: " . $e->getMessage());
        }
    }
    
    /**
     * Send welcome email to user
     * @param string $name
     * @param string $email
     * @return bool
     */
    public function sendWelcomeEmail($name, $email) {
        try {
            // Clear any previous recipients
            $this->mail->clearAddresses();
            
            // Add recipient
            $this->mail->addAddress($email, $name);
            
            // Email content
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Welcome to ICS 2.2! Account Verification';
            
            // Personalized HTML email body
            $this->mail->Body = $this->generateEmailHTML($name, $email);
            
            // Plain text alternative
            $this->mail->AltBody = $this->generateEmailText($name);
            
            // Send email
            $this->mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            throw new Exception("Failed to send email: " . $e->getMessage());
        }
    }
    
    /**
     * Generate HTML email content
     * @param string $name
     * @param string $email
     * @return string
     */
    private function generateEmailHTML($name, $email) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px;'>
                <h2 style='color: #333; margin-bottom: 20px;'>Welcome to ICS 2.2! Account Verification</h2>
                
                <p><strong>Hello {$name},</strong></p>
                
                <p>You requested an account on ICS 2.2</p>
                
                <p>In order to use this account you need to <strong>Click Here</strong> to complete the registration process.</p>
                
                <div style='background-color: #e9ecef; padding: 15px; border-radius: 3px; margin: 20px 0;'>
                    <p style='margin: 5px 0;'><strong>Account Details:</strong></p>
                    <p style='margin: 5px 0;'>Email: {$email}</p>
                    <p style='margin: 5px 0;'>Registration Date: " . date('Y-m-d H:i:s') . "</p>
                </div>
                
                <p>Regards,<br>
                <strong>Systems Admin</strong><br>
                ICS 2.2</p>
                
                <hr style='margin-top: 30px;'>
                <p style='font-size: 12px; color: #666;'>
                    This is an automated message. Please do not reply to this email.
                </p>
            </div>
        </div>";
    }
    
    /**
     * Generate plain text email content
     * @param string $name
     * @return string
     */
    private function generateEmailText($name) {
        return "
        Welcome to ICS 2.2! Account Verification
        
        Hello {$name},
        
        You requested an account on ICS 2.2
        
        In order to use this account you need to click the verification link to complete the registration process.
        
        Registration Date: " . date('Y-m-d H:i:s') . "
        
        Regards,
        Systems Admin
        ICS 2.2
        ";
    }
    
    /**
     * Process user registration (main function)
     * @param string $name
     * @param string $email
     * @return array
     */
    public function registerUser($name, $email) {
        try {
            // Validate inputs
            if (!$this->validateName($name)) {
                throw new Exception("Invalid name. Name must be at least 2 characters long.");
            }
            
            if (!$this->validateEmail($email)) {
                throw new Exception("Invalid email address format.");
            }
            
            // Save user to database
            $userId = $this->saveUser($name, $email);
            
            // Send welcome email
            $emailSent = $this->sendWelcomeEmail($name, $email);
            
            return [
                'success' => true,
                'message' => 'User registered successfully and welcome email sent!',
                'user_id' => $userId,
                'email_sent' => $emailSent
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    try {
        $emailService = new EmailService();
        $result = $emailService->registerUser($name, $email);
        
        if ($result['success']) {
            echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px;'>";
            echo "✓ " . $result['message'];
            echo "</div>";
        } else {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
            echo "✗ " . $result['message'];
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
        echo "✗ Error: " . $e->getMessage();
        echo "</div>";
    }
}

// Display registration form
?>
<!DOCTYPE html>
<html>
<head>
    <title>ICS 2.2 Registration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .users-list { margin-top: 30px; border: 1px solid #ddd; padding: 15px; }
    </style>
</head>
<body>
    <h1>ICS 2.2 User Registration</h1>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <button type="submit">Register & Send Welcome Email</button>
    </form>
    
    <?php
    // Display list of registered users (Part II requirement)
    try {
        $emailService = new EmailService();
        $users = $emailService->getAllUsers();
        
        if (!empty($users)) {
            echo "<div class='users-list'>";
            echo "<h3>Registered Users (Numbered List - Part II)</h3>";
            echo "<ol>";
            foreach ($users as $index => $user) {
                echo "<li>";
                echo "<strong>" . htmlspecialchars($user['name']) . "</strong> ";
                echo "(" . htmlspecialchars($user['email']) . ") ";
                echo "- Registered: " . date('M j, Y g:i A', strtotime($user['created_at']));
                echo "</li>";
            }
            echo "</ol>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<p>Error loading users: " . $e->getMessage() . "</p>";
    }
    ?>
</body>
</html>