<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "problem_management";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Metrics
$totalComplaints = $conn->query("SELECT COUNT(*) as cnt FROM issues")->fetch_assoc()['cnt'];
$pendingReview = $conn->query("SELECT COUNT(*) as cnt FROM issues WHERE status='open' OR status='pending'")->fetch_assoc()['cnt'];
$resolvedToday = $conn->query("SELECT COUNT(*) as cnt FROM issues WHERE status='resolved' AND DATE(date) = CURDATE()")->fetch_assoc()['cnt'];
$highPriority = $conn->query("SELECT COUNT(*) as cnt FROM issues WHERE status='open' AND supports >= 20")->fetch_assoc()['cnt'];

// Complaints list
$complaints = $conn->query("SELECT * FROM issues ORDER BY date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ...existing code... -->
</head>
<body>
    <!-- ...existing code... -->
    <div class="container">
        <h1 class="mb-4 text-center fw-bold text-slate-800">Admin Dashboard</h1>
        <div class="row g-4 mb-5">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="dashboard-card p-4 text-center">
                    <div class="dashboard-icon text-primary"><i class="fa-solid fa-comment"></i></div>
                    <div class="dashboard-metric text-primary"><?php echo $totalComplaints; ?></div>
                    <div class="dashboard-label">Total Complaints</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="dashboard-card p-4 text-center">
                    <div class="dashboard-icon text-warning"><i class="fa-solid fa-clock"></i></div>
                    <div class="dashboard-metric text-warning"><?php echo $pendingReview; ?></div>
                    <div class="dashboard-label">Pending Review</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="dashboard-card p-4 text-center">
                    <div class="dashboard-icon text-success"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="dashboard-metric text-success"><?php echo $resolvedToday; ?></div>
                    <div class="dashboard-label">Resolved Today</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="dashboard-card p-4 text-center">
                    <div class="dashboard-icon text-danger"><i class="fa-solid fa-circle-exclamation"></i></div>
                    <div class="dashboard-metric text-danger"><?php echo $highPriority; ?></div>
                    <div class="dashboard-label">High Priority</div>
                </div>
            </div>
        </div>

        <div class="card mb-5 rounded-4 shadow-sm">
            <div class="card-header bg-white border-bottom-0 py-3">
                <h4 class="mb-0 fw-bold text-slate-800"><i class="fa-solid fa-chart-simple me-2 text-info"></i>Complaint Queue</h4>
                <span class="text-muted">Manage and prioritize complaints based on community impact</span>
            </div>
            <div class="card-body bg-white">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Date Filed</th>
                                <th>Supporters</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $complaints->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['category'] ?: 'Uncategorized'); ?></td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td><?php echo htmlspecialchars($row['date']); ?></td>
                                <td><span class="badge bg-info text-dark"><?php echo $row['supports']; ?></span></td>
                                <td>
                                    <?php
                                        $status = strtolower($row['status']);
                                        if ($status == 'open' || $status == 'pending') {
                                            echo '<span class="badge bg-warning text-dark">'.ucfirst($status).'</span>';
                                        } elseif ($status == 'resolved') {
                                            echo '<span class="badge bg-success">'.ucfirst($status).'</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">'.ucfirst($status).'</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary">View</button>
                                        <button class="btn btn-sm btn-outline-success">Resolve</button>
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <span class="fw-bold text-primary">
                        <?php echo $complaints->num_rows; ?>
                    </span> found
                </div>
            </div>
        </div>
    </div>
    <?php $conn->close(); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>