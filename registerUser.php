<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

file_put_contents("php_register_log.txt", "STARTED SCRIPT\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // This is a browser request, not Unity — show something visible
    header('Content-Type: application/json');
    echo json_encode(["message" => "registerUser.php is reachable but expects POST data."]);
    exit;
}

header('Content-Type: application/json');
file_put_contents("php_register_log.txt", "----\nRAW POST: " . file_get_contents("php://input") . "\n", FILE_APPEND);
file_put_contents("php_register_log.txt", "PARSED POST: " . print_r($_POST, true), FILE_APPEND);

$host = 'hostname'; // Replace with your actual host name
$db = 'dbname';     // Replace with your actual database name
$user = '';         // Replace with your actual username
$pass = '';         // Replace with your actual password

$conn = mysqli_init();
mysqli_real_connect($conn, $host, $user, $pass, $dbname, 3306, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_error) {
    file_put_contents("php_register_log.txt", "DB connection error: " . $conn->connect_error . "\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "DB connection error"]);
    exit;
}

$username = $_POST['username'] ?? '';

if (empty($username)) {
    echo json_encode(['success' => false, 'error' => 'Username is required.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO players (username) VALUES (?);");

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("s", $username);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'User registered successfully.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
