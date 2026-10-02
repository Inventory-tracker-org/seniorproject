<?php
$db_host = $_ENV['DB_HOST'] ?? NULL;
$db_port = $_ENV['DB_PORT'] ?? NULL;
$db_name = $_ENV['DB_NAME'] ?? NULL;
$db_user = $_ENV['DB_USER'] ?? NULL;
$db_pass = $_ENV['DB_PASS'] ?? NULL;
$db_pass = $_ENV['DB_PASS'] ?? NULL;
$sburl = $_ENV['SB_URL'] ?? NULL;
$sbsecret = $_ENV['SB_SECRET_KEY'] ?? NULL;

define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost:3000');

function url(string $path = ''): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

try {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.gc_maxlifetime', 43200);
        ini_set('display_errors', '0');
        ini_set('error_logs', '1');
        ini_set('display_startup_errors', '0');
        ini_set('error_reporting', (string)E_ALL);
        ini_set('error_log', '/var/log/php/app-error.log');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.cookie_secure', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '1000');
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', '700');
        ini_set('max_input_time', '120');
        ini_set('upload_max_filesize', '30M');
        ini_set('default_charset', 'UTF-8');
        session_set_cookie_params(43200);
        session_start();
        $_SESSION['app_pass'] = $_ENV['APP_PASS'] ?? NULL;
    }
    $dbh = new PDO("pgsql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass, array());
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}
function check_auth($level = 'low')
{
    $levels = [
        'low' => ['user', 'custodian', 'admin', 'management'],
        'medium' => ['custodian', 'admin', 'management'],
        'high' => ['admin']
    ];
    if (!isset($_SESSION['role']) || $_SESSION['role'] === '') {
        header('Location:' . url('auth/login.php'));
        //header('Location: https://dataworks-7b7x.onrender.com/auth/login.php');
        exit;
    }
    $user_role = $_SESSION['role'];
    if (!in_array($user_role, $levels[$level] ?? [])) {
        header('Location: ' . url('index.php'));
        //header('Location: https://dataworks-7b7x.onrender.com/index.php');
        exit;
    }
    return true;
}

function check_api_auth(PDO $dbh, string $requiredLevel = 'low'): array
{
    $levels = [
        'low' => ['user', 'custodian', 'admin', 'management'],
        'medium' => ['custodian', 'admin', 'management'],
        'high' => ['admin']
    ];

    if (!array_key_exists($requiredLevel, $levels)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid permission level configuration'
        ]);
        exit;
    }

    /*
     * Read the Authorization header.
     * The $_SERVER fallbacks are useful when getallheaders()
     * is unavailable or the web server exposes it differently.
     */
    $headers = function_exists('getallheaders')
        ? getallheaders()
        : [];

    $authHeader =
        $headers['Authorization'] ??
        $headers['authorization'] ??
        $_SERVER['HTTP_AUTHORIZATION'] ??
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ??
        '';

    if (
        !preg_match(
            '/^Bearer\s+(\S+)$/i',
            trim($authHeader),
            $matches
        )
    ) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentication token required'
        ]);
        exit;
    }

    $rawToken = $matches[1];

    /*
     * Use this only if login.php stores:
     *
     * hash('sha256', $rawToken)
     */
    $tokenHash = hash('sha256', $rawToken);

    $stmt = $dbh->prepare(
        'SELECT
            id,
            username,
            email,
            f_name,
            l_name,
            u_role,
            dept_id,
            is_active
         FROM user_table
         WHERE api_token = ?
           AND token_expires > CURRENT_TIMESTAMP
           AND is_active = TRUE
         LIMIT 1'
    );

    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid or expired token'
        ]);
        exit;
    }

    $userRole = $user['u_role'];

    if (!in_array($userRole, $levels[$requiredLevel], true)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Insufficient permissions'
        ]);
        exit;
    }

    return $user;
}
