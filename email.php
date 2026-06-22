<?php
// ============================================================
// EMAIL CONFIGURATION & HANDLER
// ============================================================
// File: config/email.php
// Description: Complete email system with SMTP support,
//              templates, and email sending functions.
// ============================================================

// ============================================================
// PREVENT DIRECT ACCESS
// ============================================================
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

// ============================================================
// EMAIL CONFIGURATION
// ============================================================

// Default Email Settings
define('EMAIL_DEFAULT_FROM_NAME', 'LGK Tech Solutions');
define('EMAIL_DEFAULT_FROM_EMAIL', 'noreply@lgktech.com');
define('EMAIL_REPLY_TO', 'glenklaisa@gmail.com');
define('EMAIL_ADMIN', 'glenklaisa@gmail.com');

// SMTP Configuration (from environment variables)
define('SMTP_ENABLED', getenv('SMTP_ENABLED') ?: false);
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls'); // tls, ssl, none
define('SMTP_AUTH', getenv('SMTP_AUTH') ?: true);

// Email Queue Settings
define('EMAIL_QUEUE_ENABLED', false);
define('EMAIL_MAX_ATTEMPTS', 3);
define('EMAIL_RETRY_DELAY', 300); // 5 minutes

// ============================================================
// EMAIL CLASS
// ============================================================

class Email {
    
    private $to;
    private $subject;
    private $message;
    private $headers;
    private $attachments = [];
    private $from_name;
    private $from_email;
    private $reply_to;
    private $cc = [];
    private $bcc = [];
    private $is_html = false;
    private $template_data = [];
    private $template_name = '';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->from_name = EMAIL_DEFAULT_FROM_NAME;
        $this->from_email = EMAIL_DEFAULT_FROM_EMAIL;
        $this->reply_to = EMAIL_REPLY_TO;
    }
    
    /**
     * Set recipient
     */
    public function to($email, $name = '') {
        $this->to = $name ? "$name <$email>" : $email;
        return $this;
    }
    
    /**
     * Set subject
     */
    public function subject($subject) {
        $this->subject = $subject;
        return $this;
    }
    
    /**
     * Set message content
     */
    public function message($message) {
        $this->message = $message;
        return $this;
    }
    
    /**
     * Set HTML content
     */
    public function html($html) {
        $this->message = $html;
        $this->is_html = true;
        return $this;
    }
    
    /**
     * Set plain text content
     */
    public function text($text) {
        $this->message = $text;
        $this->is_html = false;
        return $this;
    }
    
    /**
     * Set sender
     */
    public function from($email, $name = '') {
        $this->from_email = $email;
        $this->from_name = $name ?: EMAIL_DEFAULT_FROM_NAME;
        return $this;
    }
    
    /**
     * Set reply-to
     */
    public function replyTo($email) {
        $this->reply_to = $email;
        return $this;
    }
    
    /**
     * Add CC recipient
     */
    public function cc($email, $name = '') {
        $this->cc[] = $name ? "$name <$email>" : $email;
        return $this;
    }
    
    /**
     * Add BCC recipient
     */
    public function bcc($email, $name = '') {
        $this->bcc[] = $name ? "$name <$email>" : $email;
        return $this;
    }
    
    /**
     * Add attachment
     */
    public function attach($file_path, $name = '') {
        if (file_exists($file_path)) {
            $this->attachments[] = [
                'path' => $file_path,
                'name' => $name ?: basename($file_path)
            ];
        }
        return $this;
    }
    
    /**
     * Load email template
     */
    public function template($template_name, $data = []) {
        $this->template_name = $template_name;
        $this->template_data = $data;
        return $this;
    }
    
    /**
     * Send email
     */
    public function send() {
        // Build email
        $this->buildEmail();
        
        // Check if SMTP is enabled
        if (SMTP_ENABLED && SMTP_HOST) {
            return $this->sendSMTP();
        } else {
            return $this->sendMail();
        }
    }
    
    /**
     * Build email content and headers
     */
    private function buildEmail() {
        // Load template if specified
        if ($this->template_name) {
            $this->message = $this->loadTemplate($this->template_name, $this->template_data);
            $this->is_html = true;
        }
        
        // Build headers
        $headers = [];
        $headers[] = "From: {$this->from_name} <{$this->from_email}>";
        $headers[] = "Reply-To: {$this->reply_to}";
        
        if (!empty($this->cc)) {
            $headers[] = "CC: " . implode(', ', $this->cc);
        }
        
        if (!empty($this->bcc)) {
            $headers[] = "BCC: " . implode(', ', $this->bcc);
        }
        
        // Content type
        if ($this->is_html) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        $headers[] = "X-Mailer: PHP/" . phpversion();
        $headers[] = "X-Priority: 3";
        
        $this->headers = implode("\r\n", $headers);
    }
    
    /**
     * Send using PHP mail() function
     */
    private function sendMail() {
        try {
            $sent = mail($this->to, $this->subject, $this->message, $this->headers);
            
            if ($sent) {
                $this->logEmail('sent', 'mail');
                return ['success' => true, 'method' => 'mail'];
            } else {
                throw new Exception('Mail function failed');
            }
        } catch (Exception $e) {
            $this->logEmail('failed', 'mail', $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send using SMTP
     */
    private function sendSMTP() {
        // If attachments exist, send with attachments
        if (!empty($this->attachments)) {
            return $this->sendSMTPWithAttachments();
        }
        
        // Simple SMTP (without attachments)
        try {
            $smtp = new SMTP();
            $smtp->host = SMTP_HOST;
            $smtp->port = SMTP_PORT;
            $smtp->username = SMTP_USERNAME;
            $smtp->password = SMTP_PASSWORD;
            $smtp->secure = SMTP_SECURE;
            $smtp->auth = SMTP_AUTH;
            
            $sent = $smtp->send(
                $this->to,
                $this->from_email,
                $this->subject,
                $this->message,
                $this->is_html,
                $this->headers
            );
            
            if ($sent) {
                $this->logEmail('sent', 'smtp');
                return ['success' => true, 'method' => 'smtp'];
            } else {
                throw new Exception('SMTP send failed');
            }
        } catch (Exception $e) {
            $this->logEmail('failed', 'smtp', $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send SMTP with attachments
     */
    private function sendSMTPWithAttachments() {
        try {
            // Build multipart message
            $boundary = md5(time());
            $headers = [];
            $headers[] = "From: {$this->from_name} <{$this->from_email}>";
            $headers[] = "Reply-To: {$this->reply_to}";
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: multipart/mixed; boundary=\"$boundary\"";
            
            $this->headers = implode("\r\n", $headers);
            
            // Build message body
            $body = "--$boundary\r\n";
            $body .= "Content-Type: " . ($this->is_html ? "text/html" : "text/plain") . "; charset=UTF-8\r\n\r\n";
            $body .= $this->message . "\r\n\r\n";
            
            // Add attachments
            foreach ($this->attachments as $attachment) {
                $file_content = file_get_contents($attachment['path']);
                $file_content = chunk_split(base64_encode($file_content));
                $file_name = $attachment['name'];
                
                $body .= "--$boundary\r\n";
                $body .= "Content-Type: application/octet-stream; name=\"$file_name\"\r\n";
                $body .= "Content-Disposition: attachment; filename=\"$file_name\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $body .= $file_content . "\r\n\r\n";
            }
            
            $body .= "--$boundary--";
            
            // Send using SMTP
            $smtp = new SMTP();
            $smtp->host = SMTP_HOST;
            $smtp->port = SMTP_PORT;
            $smtp->username = SMTP_USERNAME;
            $smtp->password = SMTP_PASSWORD;
            $smtp->secure = SMTP_SECURE;
            $smtp->auth = SMTP_AUTH;
            
            // Use the SMTP class to send
            $sent = $smtp->sendRaw(
                $this->to,
                $this->from_email,
                $this->subject,
                $body,
                $this->headers
            );
            
            if ($sent) {
                $this->logEmail('sent', 'smtp_attachments');
                return ['success' => true, 'method' => 'smtp_attachments'];
            } else {
                throw new Exception('SMTP with attachments failed');
            }
        } catch (Exception $e) {
            $this->logEmail('failed', 'smtp_attachments', $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Load email template
     */
    private function loadTemplate($template_name, $data = []) {
        $template_path = ROOT_PATH . 'templates/emails/' . $template_name . '.php';
        
        if (!file_exists($template_path)) {
            // Fallback template
            return $this->buildFallbackTemplate($data);
        }
        
        // Extract data for template
        extract($data);
        
        // Start output buffering
        ob_start();
        include $template_path;
        $content = ob_get_clean();
        
        return $content;
    }
    
    /**
     * Build fallback template
     */
    private function buildFallbackTemplate($data) {
        $name = $data['name'] ?? 'there';
        $message = $data['message'] ?? '';
        $year = date('Y');
        
        $html = "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>" . ($this->subject ?? 'Email') . "</title>
            <style>
                body { font-family: Arial, sans-serif; background: #0b1224; color: #ffffff; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.05); border-radius: 12px; padding: 30px; }
                .header { border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 20px; }
                .logo { font-size: 24px; font-weight: 700; color: #00d4ff; }
                .content { line-height: 1.8; color: #e0e0e0; }
                .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; color: #666; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>LGK Tech Solutions</div>
                </div>
                <div class='content'>
                    <p>Hello " . htmlspecialchars($name) . ",</p>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                <div class='footer'>
                    &copy; $year LGK Tech Solutions. All rights reserved.
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    /**
     * Log email activity
     */
    private function logEmail($status, $method, $error = '') {
        $log_dir = ROOT_PATH . 'logs/emails/';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . 'email_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $log_entry = sprintf(
            "[%s] Status: %s | Method: %s | To: %s | Subject: %s | IP: %s",
            $timestamp,
            $status,
            $method,
            $this->to,
            $this->subject,
            $ip
        );
        
        if ($error) {
            $log_entry .= " | Error: $error";
        }
        
        $log_entry .= PHP_EOL;
        
        error_log($log_entry, 3, $log_file);
    }
}

// ============================================================
// SIMPLE SMTP CLASS
// ============================================================

class SMTP {
    public $host;
    public $port;
    public $username;
    public $password;
    public $secure;
    public $auth;
    public $timeout = 30;
    
    private $connection;
    private $last_response;
    
    /**
     * Connect to SMTP server
     */
    private function connect() {
        $host = $this->host;
        $port = $this->port;
        
        // Add secure protocol
        if ($this->secure === 'ssl') {
            $host = 'ssl://' . $host;
            $port = 465;
        }
        
        $this->connection = fsockopen($host, $port, $errno, $errstr, $this->timeout);
        
        if (!$this->connection) {
            throw new Exception("SMTP connection failed: $errstr ($errno)");
        }
        
        // Read greeting
        $this->getResponse();
        
        // EHLO
        fputs($this->connection, "EHLO " . gethostname() . "\r\n");
        $this->getResponse();
        
        // STARTTLS if using TLS
        if ($this->secure === 'tls') {
            fputs($this->connection, "STARTTLS\r\n");
            $this->getResponse();
            stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // EHLO again after TLS
            fputs($this->connection, "EHLO " . gethostname() . "\r\n");
            $this->getResponse();
        }
        
        // Authentication
        if ($this->auth && $this->username && $this->password) {
            fputs($this->connection, "AUTH LOGIN\r\n");
            $this->getResponse();
            
            fputs($this->connection, base64_encode($this->username) . "\r\n");
            $this->getResponse();
            
            fputs($this->connection, base64_encode($this->password) . "\r\n");
            $this->getResponse();
        }
        
        return true;
    }
    
    /**
     * Get SMTP response
     */
    private function getResponse() {
        $response = '';
        while ($line = fgets($this->connection, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        
        $this->last_response = $response;
        
        // Check if response starts with 2xx (success)
        if (substr($response, 0, 1) != '2') {
            throw new Exception("SMTP error: $response");
        }
        
        return $response;
    }
    
    /**
     * Send email using SMTP
     */
    public function send($to, $from, $subject, $message, $is_html = false, $headers = '') {
        try {
            $this->connect();
            
            // Mail from
            fputs($this->connection, "MAIL FROM: <$from>\r\n");
            $this->getResponse();
            
            // Recipient
            fputs($this->connection, "RCPT TO: <$to>\r\n");
            $this->getResponse();
            
            // Data
            fputs($this->connection, "DATA\r\n");
            $this->getResponse();
            
            // Headers
            $headers = "Subject: $subject\r\n";
            $headers .= "From: <$from>\r\n";
            $headers .= "To: <$to>\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: " . ($is_html ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
            
            // Send email
            fputs($this->connection, $headers . "\r\n" . $message . "\r\n.\r\n");
            $this->getResponse();
            
            // Quit
            fputs($this->connection, "QUIT\r\n");
            fclose($this->connection);
            
            return true;
            
        } catch (Exception $e) {
            if ($this->connection) {
                fclose($this->connection);
            }
            throw $e;
        }
    }
    
    /**
     * Send raw email with custom headers
     */
    public function sendRaw($to, $from, $subject, $body, $headers) {
        try {
            $this->connect();
            
            // Mail from
            fputs($this->connection, "MAIL FROM: <$from>\r\n");
            $this->getResponse();
            
            // Recipient
            fputs($this->connection, "RCPT TO: <$to>\r\n");
            $this->getResponse();
            
            // Data
            fputs($this->connection, "DATA\r\n");
            $this->getResponse();
            
            // Send
            fputs($this->connection, $headers . "\r\n\r\n" . $body . "\r\n.\r\n");
            $this->getResponse();
            
            // Quit
            fputs($this->connection, "QUIT\r\n");
            fclose($this->connection);
            
            return true;
            
        } catch (Exception $e) {
            if ($this->connection) {
                fclose($this->connection);
            }
            throw $e;
        }
    }
}

// ============================================================
// EMAIL HELPER FUNCTIONS
// ============================================================

/**
 * Send a simple email
 */
function sendSimpleEmail($to, $subject, $message, $from = null, $is_html = false) {
    $email = new Email();
    $email->to($to)
          ->subject($subject)
          ->message($message);
    
    if ($from) {
        $email->from($from);
    }
    
    if ($is_html) {
        $email->html($message);
    }
    
    return $email->send();
}

/**
 * Send a templated email
 */
function sendTemplateEmail($to, $subject, $template, $data = [], $from = null) {
    $email = new Email();
    $email->to($to)
          ->subject($subject)
          ->template($template, $data);
    
    if ($from) {
        $email->from($from);
    }
    
    return $email->send();
}

/**
 * Send contact form email
 */
function sendContactEmail($name, $email, $message, $subject = 'New Contact Message') {
    $data = [
        'name' => $name,
        'email' => $email,
        'message' => $message,
        'subject' => $subject
    ];
    
    return sendTemplateEmail(
        EMAIL_ADMIN,
        $subject . ' from ' . $name,
        'contact',
        $data,
        $email
    );
}

/**
 * Send feedback email
 */
function sendFeedbackEmail($name, $email, $feedback, $rating, $service = '') {
    $data = [
        'name' => $name,
        'email' => $email,
        'feedback' => $feedback,
        'rating' => $rating,
        'service' => $service,
        'stars' => str_repeat('⭐', $rating)
    ];
    
    return sendTemplateEmail(
        EMAIL_ADMIN,
        'New Feedback from ' . $name . ' (' . $rating . '⭐)',
        'feedback',
        $data,
        $email
    );
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($email, $token, $name = '') {
    $reset_link = SITE_URL . 'admin/reset-password.php?token=' . $token;
    
    $data = [
        'name' => $name ?: 'User',
        'email' => $email,
        'reset_link' => $reset_link,
        'expires' => '1 hour'
    ];
    
    return sendTemplateEmail(
        $email,
        'Password Reset Request - LGK Tech Solutions',
        'password_reset',
        $data,
        EMAIL_DEFAULT_FROM_EMAIL
    );
}

/**
 * Send newsletter welcome email
 */
function sendNewsletterWelcomeEmail($email, $name = '') {
    $data = [
        'name' => $name ?: 'there',
        'email' => $email,
        'unsubscribe_link' => SITE_URL . 'api/unsubscribe.php?email=' . urlencode($email)
    ];
    
    return sendTemplateEmail(
        $email,
        'Welcome to LGK Tech Solutions Newsletter!',
        'newsletter_welcome',
        $data,
        EMAIL_DEFAULT_FROM_EMAIL
    );
}

// ============================================================
// EMAIL TEMPLATE DIRECTORY
// ============================================================

// Create template directory if it doesn't exist
$template_dir = ROOT_PATH . 'templates/emails/';
if (!is_dir($template_dir)) {
    mkdir($template_dir, 0755, true);
}

// ============================================================
// DEFAULT EMAIL TEMPLATES
// ============================================================

/**
 * Create default email templates if they don't exist
 */
function createDefaultEmailTemplates() {
    $template_dir = ROOT_PATH . 'templates/emails/';
    
    // Contact template
    $contact_template = '<?php
    $name = $data["name"] ?? "";
    $email = $data["email"] ?? "";
    $message = $data["message"] ?? "";
    $subject = $data["subject"] ?? "Contact Message";
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $subject; ?></title>
        <style>
            body { font-family: Arial, sans-serif; background: #0b1224; color: #ffffff; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.05); border-radius: 12px; padding: 30px; }
            .header { border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 20px; }
            .logo { font-size: 24px; font-weight: 700; color: #00d4ff; }
            .label { color: #00d4ff; font-weight: 600; }
            .content { line-height: 1.8; }
            .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; color: #666; text-align: center; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">LGK Tech Solutions</div>
            </div>
            <h2>New Contact Message</h2>
            <div class="content">
                <p><span class="label">Name:</span> <?php echo htmlspecialchars($name); ?></p>
                <p><span class="label">Email:</span> <?php echo htmlspecialchars($email); ?></p>
                <p><span class="label">Subject:</span> <?php echo htmlspecialchars($subject); ?></p>
                <p><span class="label">Message:</span></p>
                <p><?php echo nl2br(htmlspecialchars($message)); ?></p>
            </div>
            <div class="footer">
                &copy; <?php echo date("Y"); ?> LGK Tech Solutions. All rights reserved.
            </div>
        </div>
    </body>
    </html>';
    
    file_put_contents($template_dir . 'contact.php', $contact_template);
    
    // Feedback template
    $feedback_template = '<?php
    $name = $data["name"] ?? "";
    $email = $data["email"] ?? "";
    $feedback = $data["feedback"] ?? "";
    $rating = $data["rating"] ?? 0;
    $stars = $data["stars"] ?? "";
    $service = $data["service"] ?? "";
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>New Feedback</title>
        <style>
            body { font-family: Arial, sans-serif; background: #0b1224; color: #ffffff; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.05); border-radius: 12px; padding: 30px; }
            .header { border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 20px; }
            .logo { font-size: 24px; font-weight: 700; color: #00d4ff; }
            .label { color: #00d4ff; font-weight: 600; }
            .stars { font-size: 24px; }
            .content { line-height: 1.8; }
            .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; color: #666; text-align: center; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">LGK Tech Solutions</div>
            </div>
            <h2>New Feedback Received</h2>
            <div class="content">
                <p><span class="label">Name:</span> <?php echo htmlspecialchars($name); ?></p>
                <p><span class="label">Email:</span> <?php echo htmlspecialchars($email); ?></p>
                <?php if ($service): ?>
                    <p><span class="label">Service:</span> <?php echo htmlspecialchars($service); ?></p>
                <?php endif; ?>
                <p><span class="label">Rating:</span> <span class="stars"><?php echo $stars; ?></span> (<?php echo $rating; ?>/5)</p>
                <p><span class="label">Feedback:</span></p>
                <p><?php echo nl2br(htmlspecialchars($feedback)); ?></p>
            </div>
            <div class="footer">
                &copy; <?php echo date("Y"); ?> LGK Tech Solutions. All rights reserved.
            </div>
        </div>
    </body>
    </html>';
    
    file_put_contents($template_dir . 'feedback.php', $feedback_template);
    
    // Password reset template
    $reset_template = '<?php
    $name = $data["name"] ?? "";
    $reset_link = $data["reset_link"] ?? "";
    $expires = $data["expires"] ?? "1 hour";
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Reset</title>
        <style>
            body { font-family: Arial, sans-serif; background: #0b1224; color: #ffffff; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.05); border-radius: 12px; padding: 30px; }
            .header { border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 20px; }
            .logo { font-size: 24px; font-weight: 700; color: #00d4ff; }
            .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #00d4ff, #00ffa6); color: #0b1224; font-weight: 600; border-radius: 8px; text-decoration: none; margin: 15px 0; }
            .content { line-height: 1.8; }
            .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; color: #666; text-align: center; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">LGK Tech Solutions</div>
            </div>
            <h2>Password Reset Request</h2>
            <div class="content">
                <p>Hello <?php echo htmlspecialchars($name); ?>,</p>
                <p>We received a request to reset your password for your LGK Tech Solutions account.</p>
                <p>Click the button below to reset your password:</p>
                <p style="text-align: center;">
                    <a href="<?php echo $reset_link; ?>" class="btn">Reset Password</a>
                </p>
                <p>This link will expire in <?php echo $expires; ?>.</p>
                <p>If you didn\'t request this, please ignore this email.</p>
            </div>
            <div class="footer">
                &copy; <?php echo date("Y"); ?> LGK Tech Solutions. All rights reserved.
            </div>
        </div>
    </body>
    </html>';
    
    file_put_contents($template_dir . 'password_reset.php', $reset_template);
    
    // Newsletter welcome template
    $newsletter_template = '<?php
    $name = $data["name"] ?? "";
    $unsubscribe_link = $data["unsubscribe_link"] ?? "";
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Welcome to Our Newsletter</title>
        <style>
            body { font-family: Arial, sans-serif; background: #0b1224; color: #ffffff; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.05); border-radius: 12px; padding: 30px; }
            .header { border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 20px; }
            .logo { font-size: 24px; font-weight: 700; color: #00d4ff; }
            .content { line-height: 1.8; }
            .features { background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px; margin: 15px 0; }
            .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; color: #666; text-align: center; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">LGK Tech Solutions</div>
            </div>
            <h2>Welcome to Our Newsletter! 🎉</h2>
            <div class="content">
                <p>Hello <?php echo htmlspecialchars($name); ?>,</p>
                <p>Thank you for subscribing to the LGK Tech Solutions newsletter!</p>
                <div class="features">
                    <p><strong>You\'ll receive:</strong></p>
                    <ul>
                        <li>Latest tech news and updates</li>
                        <li>Exclusive offers and discounts</li>
                        <li>Tips and tutorials for your business</li>
                        <li>New service announcements</li>
                    </ul>
                </div>
                <p>We\'re excited to have you on board!</p>
            </div>
            <div class="footer">
                <p>&copy; <?php echo date("Y"); ?> LGK Tech Solutions. All rights reserved.</p>
                <p><a href="<?php echo $unsubscribe_link; ?>" style="color: #ff4d4d;">Unsubscribe</a></p>
            </div>
        </div>
    </body>
    </html>';
    
    file_put_contents($template_dir . 'newsletter_welcome.php', $newsletter_template);
}

// Create default templates if they don't exist
createDefaultEmailTemplates();

// ============================================================
// END OF FILE
// ============================================================
?>