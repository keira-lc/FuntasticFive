<?php
// invoice_list.php - Simple Invoice Management Page
// Step 1: Start the session to check if user is logged in
session_start();

// Step 2: Check if user is NOT logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit();
}

// Step 3: Connect to database
$host = 'localhost';
$dbname = 'stylenwear_db';
$username = 'root';
$password = '';

try {
    // Create connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If connection fails, show error
    die("Cannot connect to database: " . $e->getMessage());
}

// Step 4: Get search and filter values from URL
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Step 5: Prepare SQL query
$sql = "SELECT 
            o.order_id,
            o.total_amount,
            o.currency,
            o.status as order_status,
            o.placed_at as invoice_date,
            u.fullname,
            u.email
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE 1=1"; // Start with always true condition

$params = [];

// Step 6: Add search conditions if provided
if (!empty($search)) {
    $sql .= " AND (u.fullname LIKE ? OR u.email LIKE ? OR o.order_id LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search;
}

// Step 7: Add status filter if provided
if (!empty($status) && $status != 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $status;
}

// Step 8: Add ordering
$sql .= " ORDER BY o.placed_at DESC LIMIT 50";

// Step 9: Execute the query
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Step 10: Generate invoice numbers
foreach ($invoices as &$invoice) {
    $invoice['invoice_number'] = "INV-" . date('mdY', strtotime($invoice['invoice_date'])) . "-" . $invoice['order_id'];
}
unset($invoice);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice List - Style n' Wear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
            padding-top: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .invoice-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .badge-pending { background-color: #ffc107; color: black; }
        .badge-paid { background-color: #198754; }
        .badge-shipped { background-color: #0dcaf0; color: black; }
        .badge-completed { background-color: #0d6efd; }
        .badge-cancelled { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="row">
                <div class="col-md-8">
                    <h2><i class="fas fa-file-invoice"></i> Invoice List</h2>
                    <p class="text-muted">View all customer invoices</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Search Form -->
            <form method="GET" class="mt-3">
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by name, email, or order ID..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="status" class="form-control">
                            <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $status == 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="shipped" <?php echo $status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Invoice Count -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Found <?php echo count($invoices); ?> invoice(s)
        </div>

        <!-- Invoices List -->
        <?php if (empty($invoices)): ?>
        <div class="alert alert-warning text-center">
            <i class="fas fa-exclamation-triangle fa-2x"></i><br>
            <h4>No invoices found</h4>
            <p>Try changing your search criteria</p>
        </div>
        <?php else: ?>
        <?php foreach ($invoices as $invoice): ?>
        <div class="invoice-card">
            <div class="row">
                <div class="col-md-3">
                    <strong>Invoice #:</strong><br>
                    <h5 class="text-primary"><?php echo $invoice['invoice_number']; ?></h5>
                    <small class="text-muted">Order #<?php echo $invoice['order_id']; ?></small>
                </div>
                <div class="col-md-3">
                    <strong>Customer:</strong><br>
                    <?php echo htmlspecialchars($invoice['fullname']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($invoice['email']); ?></small>
                </div>
                <div class="col-md-2">
                    <strong>Date:</strong><br>
                    <?php echo date('M d, Y', strtotime($invoice['invoice_date'])); ?>
                </div>
                <div class="col-md-2">
                    <strong>Amount:</strong><br>
                    <h5 class="text-success">₱<?php echo number_format($invoice['total_amount'], 2); ?></h5>
                </div>
                <div class="col-md-2 text-end">
                    <span class="badge badge-<?php echo $invoice['order_status']; ?>">
                        <?php echo ucfirst($invoice['order_status']); ?>
                    </span><br><br>
                    <a href="invoice_view.php?id=<?php echo $invoice['order_id']; ?>" 
                       class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> View
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Add Font Awesome for icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <script>
        // Simple JavaScript for better UX
        document.addEventListener('DOMContentLoaded', function() {
            // Make invoice cards clickable
            document.querySelectorAll('.invoice-card').forEach(card => {
                card.style.cursor = 'pointer';
                card.addEventListener('click', function(e) {
                    if (!e.target.closest('a, button')) {
                        const viewBtn = this.querySelector('a.btn-primary');
                        if (viewBtn) {
                            window.open(viewBtn.href, '_blank');
                        }
                    }
                });
            });
            
            // Add hover effect
            document.querySelectorAll('.invoice-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 2px 5px rgba(0,0,0,0.05)';
                });
            });
        });
    </script>
</body>
</html>