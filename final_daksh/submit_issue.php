<?php
// filepath: c:\xampp\htdocs\final\submit_issue.php
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (
    !isset($data['title']) || empty($data['title']) ||
    !isset($data['category']) || empty($data['category']) ||
    !isset($data['description']) || empty($data['description']) ||
    !isset($data['location']) || empty($data['location'])
) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$title = $data['title'];
$category = $data['category'];
$description = $data['description'];
$location = $data['location'];
$date = date('Y-m-d H:i:s'); // current date and time

// Connect to database
$conn = new mysqli('localhost', 'root', '', 'problem_management');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Insert into issues table (status defaults to 'open', issue_id removed)
$stmt = $conn->prepare("INSERT INTO issues (title, description, location, date, category, status) VALUES (?, ?, ?, ?, ?, 'open')");
$stmt->bind_param("sssss", $title, $description, $location, $date, $category);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Insert failed']);
}

$stmt->close();
$conn->close();
?>