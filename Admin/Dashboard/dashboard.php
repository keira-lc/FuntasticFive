<?php
session_start();

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header('Location: client/index.php');
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'funtasticfive';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $funtasticfive = true;
} catch (PDOException $e) {
    $funtasticfive = false;
    $pdo = null;
}

// Helper to fetch a single integer metric from DB safely
function fetchCountOrFallback(PDO $pdo = null, string $sql = '', int $fallback = 0): int {
    if ($pdo === null || empty($sql)) return $fallback;
    try {
        $stmt = $pdo->query($sql);
        $val = $stmt->fetchColumn();
        return (int)$val;
    } catch (Throwable $e) {
        return $fallback;
    }
}

if ($funtasticfive) {
    $totalUsers = fetchCountOrFallback($pdo, "SELECT COUNT(*) FROM users", 0);
    $activeUsers = fetchCountOrFallback($pdo, "SELECT COUNT(*) FROM users", 0); // All users are active since no status field
    $reports    = fetchCountOrFallback($pdo, "SELECT COUNT(*) FROM orders", 0); // Using orders as reports
} else {
    $totalUsers = 124;
    $activeUsers = 98;
    $reports = 12;
}

// Load user rows on db (limit 10)
$usersRows = [];
if ($funtasticfive) {
    try {
        $stmt = $pdo->query("SELECT user_id as id, username, email, 'active' as status, date_joined as created_at FROM users ORDER BY date_joined DESC LIMIT 10");
        $usersRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $usersRows = [];
    }
} else {
    $usersRows = [
        ['id'=>1,'username'=>'Andrei','email'=>'andreiArnaldo@example.com','status'=>'active','created_at'=>'2025-01-08'],
        ['id'=>2,'username'=>'Gab','email'=>'SapicoGab@example.com','status'=>'inactive','created_at'=>'2025-02-15'],
        ['id'=>3,'username'=>'Keira','email'=>'KLCreollo@example.com','status'=>'active','created_at'=>'2025-03-20'],
    ];
}

// ========== NEW CODE FOR REAL MONTHLY DATA ==========
// Get monthly active users from database - REAL DATA
$monthlyData = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]; // Start with zeros for all months

if ($funtasticfive) {
    try {
        // This query counts users for each month based on registration date
        $sql = "SELECT 
                    MONTH(date_joined) as month_number, 
                    COUNT(*) as user_count 
                FROM users 
                GROUP BY MONTH(date_joined) 
                ORDER BY month_number";
        
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fill the monthly data array with real numbers
        foreach ($results as $row) {
            $monthIndex = $row['month_number'] - 1; // January=0, February=1, etc.
            $monthlyData[$monthIndex] = (int)$row['user_count'];
        }
        
    } catch (Throwable $e) {
        // If there's an error, keep the zeros
        error_log("Chart data error: " . $e->getMessage());
    }
}

// Convert PHP array to JavaScript format
$jsMonthlyData = json_encode($monthlyData);
// ========== END NEW CODE ==========

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    body { min-height: 100vh; }
    .sidebar { width: 220px; }
    .content { margin-left: 220px; padding: 20px; }
    @media (max-width: 767px) {
      .sidebar { position: static; width: 100%; }
      .content { margin-left: 0; }
    }
    .card-icon { font-size: 28px; opacity: .85; }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">Style n' Wear</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topbar" aria-controls="topbar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="topbar">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="profile.php"><i class="fa fa-user"></i> Profile</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="d-flex">
    <aside class="bg-light sidebar border-end position-fixed h-100">
      <div class="p-3">
        <h5>Admin Menu</h5>
        <hr>
        <ul class="nav flex-column">
          <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fa fa-dashboard me-2"></i>Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="users.php"><i class="fa fa-users me-2"></i>Users</a></li>
          <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fa fa-file-alt me-2"></i>Reports</a></li>
          <li class="nav-item"><a class="nav-link" href="settings.php"><i class="fa fa-cog me-2"></i>Settings</a></li>
        </ul>
      </div>
    </aside>

    <main class="content flex-fill">
      <div class="container-fluid">
        <div class="row mb-3">
          <div class="col">
            <h2>Dashboard</h2>
            <p class="text-muted">Welcome back, admin — quick overview of the system.</p>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-4">
            <div class="card shadow-sm">
              <div class="card-body d-flex align-items-center">
                <div class="me-3">
                  <div class="bg-primary text-white rounded-circle p-3 card-icon"><i class="fa fa-users"></i></div>
                </div>
                <div>
                  <div class="text-muted small">Total users</div>
                  <div class="fs-4 fw-bold"><?php echo e($totalUsers); ?></div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card shadow-sm">
              <div class="card-body d-flex align-items-center">
                <div class="me-3">
                  <div class="bg-success text-white rounded-circle p-3 card-icon"><i class="fa fa-user-check"></i></div>
                </div>
                <div>
                  <div class="text-muted small">Active users</div>
                  <div class="fs-4 fw-bold"><?php echo e($activeUsers); ?></div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card shadow-sm">
              <div class="card-body d-flex align-items-center">
                <div class="me-3">
                  <div class="bg-warning text-dark rounded-circle p-3 card-icon"><i class="fa fa-file-alt"></i></div>
                </div>
                <div>
                  <div class="text-muted small">Orders</div>
                  <div class="fs-4 fw-bold"><?php echo e($reports); ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row mt-4">
          <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
              <div class="card-body">
                <h5 class="card-title">Monthly User Registrations</h5>
                <canvas id="usersChart" height="120"></canvas>
              </div>
            </div>

            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title">Recent Users</h5>
                <div class="table-responsive">
                  <table class="table table-striped table-sm">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Joined</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($usersRows as $u): ?>
                      <tr>
                        <td><?php echo e($u['id']); ?></td>
                        <td><?php echo e($u['username']); ?></td>
                        <td><?php echo e($u['email']); ?></td>
                        <td><span class="badge bg-success"><?php echo e($u['status']); ?></span></td>
                        <td><?php echo e($u['created_at']); ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

          </div>

          <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
              <div class="card-body">
                <h6 class="card-title">Quick Actions</h6>
                <a href="users.php" class="btn btn-sm btn-outline-primary w-100 mb-2"><i class="fa fa-users me-2"></i>Manage Users</a>
                <a href="reports.php" class="btn btn-sm btn-outline-secondary w-100 mb-2"><i class="fa fa-file-alt me-2"></i>View Orders</a>
                <a href="settings.php" class="btn btn-sm btn-outline-dark w-100"><i class="fa fa-cog me-2"></i>Settings</a>
              </div>
            </div>

            <div class="card shadow-sm">
              <div class="card-body">
                <h6 class="card-title">System Info</h6>
                <ul class="list-unstyled small">
                  <li>PHP: <?php echo e(phpversion()); ?></li>
                  <li>DB connected: <?php echo $funtasticfive ? '<span class="text-success">yes</span>' : '<span class="text-muted">no</span>'; ?></li>
                  <li>Now: <?php echo e(date('Y-m-d H:i:s')); ?></li>
                </ul>
              </div>
            </div>

          </div>
        </div>

        <footer class="pt-4 mt-4 border-top">
          <div class="d-flex justify-content-between small">
            <div>© <?php echo date('Y'); ?> Style n' Wear Admin</div>
            <div>Built with PHP • Bootstrap</div>
          </div>
        </footer>

      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  //<script>
    const ctx = document.getElementById('usersChart').getContext('2d');
    const labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    
    // ========== REAL DATA FROM DATABASE ==========
    const data = {
      labels,
      datasets: [{
        label: 'User Registrations',
        data: <?php echo $jsMonthlyData; ?>, // REAL DATA FROM DATABASE!
        fill: true,
        tension: 0.3,
        borderColor: '#0d6efd',
        backgroundColor: 'rgba(13, 110, 253, 0.1)'
      }]
    };
    
    const config = {
      type: 'line',
      data,
      options: {
        scales: {
          y: { 
            beginAtZero: true,
            ticks: {
              stepSize: 1
            }
          }
        },
        plugins: { 
          legend: { 
            display: false 
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return Users: ${context.parsed.y};
              }
            }
          }
        }
      }
    };
    new Chart(ctx, config);
  </script>
</body>
</html>