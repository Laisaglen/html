<?php

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://lgktech.com');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

function response($success, $message, $code = 200, $extra = [])
{
    http_response_code($code);

    echo json_encode(
        array_merge([
            'success' => $success,
            'message' => $message
        ], $extra),
        JSON_PRETTY_PRINT
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(false, 'Method not allowed', 405);
}

$email = trim($_POST['email'] ?? '');
$name = trim($_POST['name'] ?? '');
$consent = isset($_POST['consent']);

$errors = [];

if (!$consent) {
    $errors[] = 'Consent required.';
}

if (empty($email)) {
    $errors[] = 'Email is required.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address.';
}

if (strlen($email) > 100) {
    $errors[] = 'Email too long.';
}

if (strlen($name) > 100) {
    $errors[] = 'Name too long.';
}

if (!empty($errors)) {
    response(false, implode(' ', $errors), 400, [
        'errors' => $errors
    ]);
}

/*
|--------------------------------------------------------------------------
| Rate Limiting
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['subscribe_attempts'])) {
    $_SESSION['subscribe_attempts'] = [];
}

$window = 3600;
$maxAttempts = 5;

$_SESSION['subscribe_attempts'] = array_filter(
    $_SESSION['subscribe_attempts'],
    fn($time) => $time > time() - $window
);

if (count($_SESSION['subscribe_attempts']) >= $maxAttempts) {
    response(
        false,
        'Too many attempts. Please try again later.',
        429
    );
}

$_SESSION['subscribe_attempts'][] = time();

/*
|--------------------------------------------------------------------------
| Subscriber Check
|--------------------------------------------------------------------------
*/

$query = "SELECT id,status FROM newsletter WHERE email = ?";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    response(false, 'Database error.', 500);
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$subscriber = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if ($subscriber) {

    if ($subscriber['status'] === 'active') {

        response(
            true,
            'You are already subscribed.',
            200,
            [
                'action' => 'already_subscribed'
            ]
        );
    }

    $update = "
        UPDATE newsletter
        SET status='active',
            name=?,
            subscribed_at=NOW()
        WHERE id=?
    ";

    $stmt = mysqli_prepare($conn, $update);

    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $name,
        $subscriber['id']
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    response(
        true,
        'Subscription reactivated.',
        200,
        [
            'action' => 'reactivated'
        ]
    );
}

/*
|--------------------------------------------------------------------------
| New Subscriber
|--------------------------------------------------------------------------
*/

$ip = $_SERVER['REMOTE_ADDR'] ?? '';

$userAgent = substr(
    $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    0,
    255
);

$source = "website";

$insert = "
INSERT INTO newsletter (
    email,
    name,
    source,
    ip_address,
    user_agent,
    status,
    subscribed_at
)
VALUES (
    ?, ?, ?, ?, ?, 'active', NOW()
)
";

$stmt = mysqli_prepare($conn, $insert);

mysqli_stmt_bind_param(
    $stmt,
    "sssss",
    $email,
    $name,
    $source,
    $ip,
    $userAgent
);

if (!mysqli_stmt_execute($stmt)) {

    if (mysqli_errno($conn) === 1062) {
        response(true, 'Already subscribed.');
    }

    response(false, 'Failed to subscribe.', 500);
}

$subscriberId = mysqli_insert_id($conn);

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| Welcome Email
|--------------------------------------------------------------------------
*/

$subject = "Welcome to LGK Tech Solutions";

$body = "
Hello {$name},

Thank you for subscribing to our newsletter.

You will receive:
- Tech updates
- New services
- Industry insights
- Special offers

Regards,
LGK Tech Solutions
";

$headers = "From: newsletter@lgktech.com\r\n";
$headers .= "Reply-To: support@lgktech.com\r\n";

$emailSent = @mail(
    $email,
    $subject,
    $body,
    $headers
);

/*
|--------------------------------------------------------------------------
| Log
|--------------------------------------------------------------------------
*/

$logDir = __DIR__ . '/../logs';

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logLine = sprintf(
    "[%s] %s | %s\n",
    date('Y-m-d H:i:s'),
    $email,
    $ip
);

file_put_contents(
    $logDir . '/newsletter.log',
    $logLine,
    FILE_APPEND
);

response(
    true,
    'Subscribed successfully.',
    200,
    [
        'subscriber_id' => $subscriberId,
        'email_sent' => $emailSent
    ]
);