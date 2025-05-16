<?php
session_start();
include_once 'db_connection.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php'); 
    exit;
}

$user = $_SESSION['user'];

// Get date range from request or default to last 30 days
$end_date = date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));

// Fetch key metrics
$metrics = [];

// Total Revenue
$stmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount), 0) as total_revenue FROM orders WHERE order_date BETWEEN ? AND ?');
$stmt->execute([$start_date, $end_date]);
$metrics['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];

// Total Orders
$stmt = $pdo->prepare('SELECT COUNT(*) as total_orders FROM orders WHERE order_date BETWEEN ? AND ?');
$stmt->execute([$start_date, $end_date]);
$metrics['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

// Average Order Value
$metrics['average_order_value'] = $metrics['total_orders'] > 0 ? 
    $metrics['total_revenue'] / $metrics['total_orders'] : 0;

// Active Users (users who placed orders)
$stmt = $pdo->prepare('SELECT COUNT(DISTINCT user_id) as active_users FROM orders WHERE order_date BETWEEN ? AND ?');
$stmt->execute([$start_date, $end_date]);
$metrics['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_users'];

// Top Selling Products
$stmt = $pdo->prepare('
    SELECT p.name, SUM(oi.quantity) as total_quantity, SUM(oi.quantity * oi.price_at_purchase) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_date BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY total_quantity DESC
    LIMIT 5
');
$stmt->execute([$start_date, $end_date]);
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Revenue by Day
$stmt = $pdo->prepare('
    SELECT DATE(order_date) as date, SUM(total_amount) as daily_revenue
    FROM orders
    WHERE order_date BETWEEN ? AND ?
    GROUP BY DATE(order_date)
    ORDER BY date
');
$stmt->execute([$start_date, $end_date]);
$revenue_by_day = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - AgriSync Admin</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4e944f;
            --secondary-color: #357a38;
            --background-color: #f5f5f5;
            --text-color: #333;
            --border-color: #ddd;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-nav {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .admin-nav a {
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-weight: bold;
        }

        .admin-nav a:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .metric-card h3 {
            margin: 0;
            color: var(--text-color);
            font-size: 1.1em;
        }

        .metric-card .value {
            font-size: 2em;
            font-weight: bold;
            color: var(--primary-color);
            margin: 10px 0;
        }

        .chart-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .chart-container h2 {
            margin-top: 0;
            color: var(--text-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        .date-filter {
            margin-bottom: 20px;
            text-align: right;
        }

        .date-filter input {
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-left: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid var(--border-color);
            text-align: left;
        }

        th {
            background-color: var(--primary-color);
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Analytics & Reports</h1>
        </div>

        <div class="admin-nav">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_users.php">Manage Users</a>
            <a href="admin_products.php">Manage Products</a>
            <a href="admin_orders.php">Manage Orders</a>
        </div>

        <div class="date-filter">
            <form method="get" action="">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                
                <button type="submit" class="submit-button">Apply Filter</button>
            </form>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <h3>Total Revenue</h3>
                <div class="value">₱<?php echo number_format($metrics['total_revenue'], 2); ?></div>
            </div>
            <div class="metric-card">
                <h3>Total Orders</h3>
                <div class="value"><?php echo number_format($metrics['total_orders']); ?></div>
            </div>
            <div class="metric-card">
                <h3>Average Order Value</h3>
                <div class="value">₱<?php echo number_format($metrics['average_order_value'], 2); ?></div>
            </div>
            <div class="metric-card">
                <h3>Active Users</h3>
                <div class="value"><?php echo number_format($metrics['active_users']); ?></div>
            </div>
        </div>

        <div class="chart-container">
            <h2>Revenue Trend</h2>
            <canvas id="revenueChart"></canvas>
        </div>

        <div class="chart-container">
            <h2>Top Selling Products</h2>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo number_format($product['total_quantity']); ?></td>
                            <td>₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="logout.php" style="color: var(--primary-color); text-decoration: none;">Logout</a>
        </div>
    </div>

    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($revenue_by_day, 'date')); ?>,
                datasets: [{
                    label: 'Daily Revenue',
                    data: <?php echo json_encode(array_column($revenue_by_day, 'daily_revenue')); ?>,
                    borderColor: '#4e944f',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Revenue Trend'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 