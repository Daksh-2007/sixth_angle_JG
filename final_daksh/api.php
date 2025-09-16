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

// Helper: send error
function send_error($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit();
}

// GET: List all issues or dashboard metrics
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['dashboard'])) {
        // Dashboard metrics and complaints
        $totalComplaints = $conn->query("SELECT COUNT(*) as cnt FROM issues")->fetchColumn();
        $pendingReview = $conn->query("SELECT COUNT(*) as cnt FROM issues WHERE status='open' OR status='pending'")->fetchColumn();
        $resolvedToday = $conn->query("SELECT COUNT(*) as cnt FROM issues WHERE status='resolved' AND DATE(date) = CURDATE()")->fetchColumn();
        $highPriority = $conn->query("SELECT COUNT(*) as cnt FROM issues WHERE status='open' AND supports >= 20")->fetchColumn();
        $stmt = $conn->prepare("SELECT id, title, description, location, date, supports, category, status FROM issues ORDER BY date DESC");
        $stmt->execute();
        $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'metrics' => [
                'totalComplaints' => $totalComplaints,
                'pendingReview' => $pendingReview,
                'resolvedToday' => $resolvedToday,
                'highPriority' => $highPriority
            ],
            'complaints' => $complaints
        ]);
        exit();
    }

    $sql = "SELECT id, title, description, location, date, supports, category, status FROM issues";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($issues);
    exit();
}

// POST: Create new issue OR Support/Undo an issue
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Support or Undo action (vote)
    if (isset($input['issueId']) && !isset($input['title'])) {
        $issueId = $input['issueId'];
        $action = isset($input['action']) ? $input['action'] : 'support';

        if ($action === 'undo') {
            // Decrement supports count, but not below zero
            $sql = "UPDATE issues SET supports = CASE WHEN supports > 0 THEN supports - 1 ELSE 0 END WHERE id = :id";
            $user_supported = false;
        } else {
            // Increment supports count
            $sql = "UPDATE issues SET supports = supports + 1 WHERE id = :id";
            $user_supported = true;
        }
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $issueId);
        $stmt->execute();

        // Fetch the updated supports count
        $sql_select = "SELECT supports FROM issues WHERE id = :id";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bindParam(':id', $issueId);
        $stmt_select->execute();
        $updated_supports = $stmt_select->fetchColumn();

        echo json_encode([
            'success' => true,
            'newSupports' => $updated_supports,
            'user_supported' => $user_supported
        ]);
        exit();
    }

    if (
        empty($input['title']) ||
        empty($input['description'])
    ) {
        send_error('Missing required fields');
    }
    $sql = "INSERT INTO issues (title, description, location, date, supports, category, status)
            VALUES (:title, :description, :location, :date, 0, :category, :status)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':title', $input['title']);
    $stmt->bindParam(':description', $input['description']);
    $stmt->bindParam(':location', $input['location']);
    $stmt->bindParam(':date', $input['date']);
    $stmt->bindParam(':category', $input['category']);
    $stmt->bindParam(':status', $input['status']);
    $stmt->execute();
    echo json_encode(['success' => true, 'id' => $conn->lastInsertId()]);
    exit();
}

// PUT: Update issue status
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['id']) || empty($input['status'])) {
        send_error('Missing id or status');
    }
    $sql = "UPDATE issues SET status = :status WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':status', $input['status']);
    $stmt->bindParam(':id', $input['id']);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit();
}

// DELETE: Delete issue
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['id'])) {
        send_error('Missing id');
    }
    $sql = "DELETE FROM issues WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $input['id']);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit();
}

$conn = null;
?>
$conn = null;
?>
