<?php
header('Content-Type: application/json');

// Database connection details
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password
$dbname = "problem_management";

// Create connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Connection failed: " . $e->getMessage()]);
    exit();
}

// Handle GET request to fetch issues
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT issue_id AS id, title, description, location, date, supports, category, status FROM issues";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($issues);
}

// Handle POST request to update supports
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read the POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['issueId'])) {
        $issueId = $data['issueId'];

        // Update the supports count in the database
        $sql = "UPDATE issues SET supports = supports + 1 WHERE issue_id = :issue_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':issue_id', $issueId);
        $stmt->execute();

        // Fetch the updated issue to return the new supports count
        $sql_select = "SELECT supports FROM issues WHERE issue_id = :issue_id";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bindParam(':issue_id', $issueId);
        $stmt_select->execute();
        $updated_supports = $stmt_select->fetchColumn();

        echo json_encode(['success' => true, 'newSupports' => $updated_supports]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request. "issueId" is missing.']);
    }
}

$conn = null;
?>
