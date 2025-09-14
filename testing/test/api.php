<?php
header('Content-Type: application/json');

session_start();
$user_id = $_SESSION['user_id'] ?? null;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "problem_management";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Connection failed: " . $e->getMessage()]);
    exit();
}

// Handle GET request to fetch issues with user-specific support status
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT issue_id AS id, title, description, location, date, supports, category, status 
            FROM issues";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($user_id) {
        $support_sql = "SELECT issue_id FROM user_supports WHERE user_id = :user_id";
        $support_stmt = $conn->prepare($support_sql);
        $support_stmt->bindParam(':user_id', $user_id);
        $support_stmt->execute();
        $supported_issues = $support_stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($issues as &$issue) {
            $issue['user_supported'] = in_array($issue['id'], $supported_issues);
        }
    }

    echo json_encode($issues);
}

// Handle POST request to update supports
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['error' => 'User not logged in']);
        exit();
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['issueId'])) {
        $issueId = $data['issueId'];

        // Check if user has already supported this issue
        $check_sql = "SELECT COUNT(*) FROM user_supports WHERE user_id = :user_id AND issue_id = :issue_id";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->bindParam(':issue_id', $issueId);
        $check_stmt->execute();
        $has_supported = $check_stmt->fetchColumn();

        if (!$has_supported) {
            // Insert user support record
            $insert_sql = "INSERT INTO user_supports (user_id, issue_id) VALUES (:user_id, :issue_id)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bindParam(':user_id', $user_id);
            $insert_stmt->bindParam(':issue_id', $issueId);
            $insert_stmt->execute();

            // Update the supports count
            $update_sql = "UPDATE issues SET supports = supports + 1 WHERE issue_id = :issue_id";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindParam(':issue_id', $issueId);
            $update_stmt->execute();

            $sql_select = "SELECT supports FROM issues WHERE issue_id = :issue_id";
            $stmt_select = $conn->prepare($sql_select);
            $stmt_select->bindParam(':issue_id', $issueId);
            $stmt_select->execute();
            $updated_supports = $stmt_select->fetchColumn();

            echo json_encode(['success' => true, 'newSupports' => $updated_supports, 'user_supported' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Already supported']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request. "issueId" is missing.']);
    }
}

$conn = null;
?>