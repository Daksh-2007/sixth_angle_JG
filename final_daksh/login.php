<?php
session_start();

// Logout functionality
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: Index.html");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "login_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user = $_POST['username'];
$pass = $_POST['password'];

// Prepared statement
$stmt = $conn->prepare("SELECT * FROM users_data WHERE username=? AND password=?");
$stmt->bind_param("ss", $user, $pass);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Redirect based on role
    if ($row['role'] === 'admin') {
        echo "<script>
                alert('✅ Admin Login Successful!');
                window.location.href = 'admin.html';
              </script>";
    } elseif ($row['role'] === 'user') {
        echo "<script>
                alert('✅ User Login Successful!');
                window.location.href = 'user.html';
              </script>";
    } else {
        echo "<script>
                alert('⚠️ Role not defined properly!');
                window.location.href = 'index.html';
              </script>";
    }
} else {
    echo "<script>
            alert('❌ Incorrect username or password!');
            window.location.href = 'index.html';
          </script>";
}

$conn->close();
?>
