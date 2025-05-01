<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

file_put_contents("php_login_log.txt", "STARTED SCRIPT\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // This is a browser request, not Unity — show something visible
    header('Content-Type: application/json');
    echo json_encode(["message" => "loginUser.php is reachable but expects POST data."]);
    exit;
}

header('Content-Type: application/json');
file_put_contents("php_login_log.txt", "----\nRAW POST: " . file_get_contents("php://input") . "\n", FILE_APPEND);
file_put_contents("php_login_log.txt", "PARSED POST: " . print_r($_POST, true), FILE_APPEND);

$host = 'hostname'; // Replace with your actual host name
$db = 'dbname';     // Replace with your actual database name
$user = '';         // Replace with your actual username
$pass = '';         // Replace with your actual password

$conn = mysqli_init();
mysqli_real_connect($conn, $host, $user, $pass, $dbname, 3306, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_error) {
    file_put_contents("php_login_log.txt", "DB connection error: " . $conn->connect_error . "\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "DB connection error"]);
    exit;
}

$username = $_POST['username'] ?? '';
file_put_contents("php_login_log.txt", "Checking username: $username\n", FILE_APPEND);

$tableCheck = $conn->query("SELECT COUNT(*) as total FROM players");
if (!$tableCheck) {
    file_put_contents("php_login_log.txt", "Table query failed: " . $conn->error . "\n", FILE_APPEND);
}
else {
    $count = $tableCheck->fetch_assoc()['total'];
    file_put_contents("php_login_log.txt", "Table player count: $count\n", FILE_APPEND);
}

$stmt = $conn->prepare("SELECT player_id FROM players WHERE username = ?");
if (!$stmt) {
    file_put_contents("php_login_log.txt", "Prepare failed: " . $conn->error . "\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "SQL prepare failed"]);
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

file_put_contents("php_login_log.txt", "Query ran. Result num rows: " . $result->num_rows . "\n", FILE_APPEND);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        "success" => true,
        "playerID" => $row['player_id']
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
}

$stmt->close();
$conn->close();