<?php
// Contact Form Handler for Tony's Greenwich Village Pizzeria
// Telegram Bot API Integration

// Configuration - Replace these with your actual values
define('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('TELEGRAM_CHAT_ID', 'YOUR_CHAT_ID_HERE');
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage');

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Function to validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to send message to Telegram
function send_to_telegram($message) {
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents(TELEGRAM_API_URL, false, $context);
    
    return $result !== false;
}

// Initialize response variables
$success = false;
$error_message = '';
$name = '';
$email = '';
$phone = '';
$message = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form inputs
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
    $message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';
    
    // Validate required fields
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!validate_email($email)) {
        $errors[] = 'Invalid email address.';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required.';
    }
    
    // If no validation errors, process the form
    if (empty($errors)) {
        // Format the message for Telegram
        $telegram_message = "<b>New Contact Form Submission</b>\n\n";
        $telegram_message .= "<b>Name:</b> " . $name . "\n";
        $telegram_message .= "<b>Email:</b> " . $email . "\n";
        
        if (!empty($phone)) {
            $telegram_message .= "<b>Phone:</b> " . $phone . "\n";
        }
        
        $telegram_message .= "\n<b>Message:</b>\n" . $message;
        
        // Send to Telegram
        if (send_to_telegram($telegram_message)) {
            $success = true;
        } else {
            $error_message = 'Sorry, there was an error sending your message. Please try calling us at (212) 555-1234.';
        }
    } else {
        $error_message = implode(' ', $errors);
    }
}

// Return JSON response for AJAX or redirect with message
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // AJAX request - return JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Thank you for your message! We\'ll get back to you soon.' : $error_message
    ]);
    exit;
} else {
    // Regular form submission - redirect back to contact page with message
    $redirect_url = 'contact.html';
    
    if ($success) {
        $redirect_url .= '?status=success&msg=' . urlencode('Thank you for your message! We\'ll get back to you soon.');
    } else {
        $redirect_url .= '?status=error&msg=' . urlencode($error_message);
        
        // Preserve form data in URL parameters (optional, for better UX you might use sessions)
        if (!empty($name)) $redirect_url .= '&name=' . urlencode($name);
        if (!empty($email)) $redirect_url .= '&email=' . urlencode($email);
        if (!empty($phone)) $redirect_url .= '&phone=' . urlencode($phone);
    }
    
    header('Location: ' . $redirect_url);
    exit;
}
?>

