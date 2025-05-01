<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

file_put_contents("php_position_log.txt", "STARTED SCRIPT\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // This is a browser request, not Unity — show something visible
    header('Content-Type: application/json');
    echo json_encode(["message" => "updatePosition.php is reachable but expects POST data."]);
    exit;
}

header('Content-Type: application/json');
file_put_contents("php_position_log.txt", "----\nRAW POST: " . file_get_contents("php://input") . "\n", FILE_APPEND);
file_put_contents("php_position_log.txt", "PARSED POST: " . print_r($_POST, true), FILE_APPEND);

$host = 'hostname'; // Replace with your actual host name
$db = 'dbname';     // Replace with your actual database name
$user = '';         // Replace with your actual username
$pass = '';         // Replace with your actual password

$conn = mysqli_init();
mysqli_real_connect($conn, $host, $user, $pass, $dbname, 3306, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_error) {
    file_put_contents("php_position_log.txt", "DB connection error: " . $conn->connect_error . "\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "DB connection error"]);
    exit;
}

$PlayerID = $_POST['player_id'];
$PosX = (float)$_POST['position_x'];
$PosY = (float)$_POST['position_y'];
$PosZ = (float)$_POST['position_z'];

if ($PlayerID === null || $PosX === null || $PosY === null || $PosZ === null) {
    echo json_encode([
        "success" => false,
        "message" => "Parameters required."
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT 1 FROM scores WHERE player_id = ?");
$stmt->bind_param("s", $PlayerID);
$stmt->execute();
$result = $stmt->get_result();
$playerExists = $result->num_rows > 0;
$stmt->close();

if ($playerExists) {
    $stmt = $conn->prepare("UPDATE scores SET position_x = ?, position_y = ?, position_z = ? WHERE player_id = ?");
    $stmt->bind_param("ddds", $PosX, $PosY, $PosZ, $PlayerID);
} else {
    $stmt = $conn->prepare("UPDATE scores SET position_x = ?, position_y = ?, position_z = ? WHERE player_id = ?");
    $stmt->bind_param("ddds", $PosX, $PosY, $PosZ, $PlayerID);
}

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Position updated successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error", 
        "message" => $stmt->error
    ]);
}   

$stmt->close();
$conn->close();
?>
