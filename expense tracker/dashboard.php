<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch user's expenses
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC");
$stmt->execute([$_SESSION['user_id']]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total expenses
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Calculate category-wise totals
$stmt = $pdo->prepare("SELECT category, SUM(amount) as category_total FROM expenses WHERE user_id = ? GROUP BY category");
$stmt->execute([$_SESSION['user_id']]);
$category_totals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate monthly totals
$stmt = $pdo->prepare("SELECT DATE_FORMAT(date, '%Y-%m') as month, SUM(amount) as monthly_total 
                      FROM expenses 
                      WHERE user_id = ? 
                      GROUP BY DATE_FORMAT(date, '%Y-%m') 
                      ORDER BY month DESC 
                      LIMIT 6");
$stmt->execute([$_SESSION['user_id']]);
$monthly_totals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate this month's total
$stmt = $pdo->prepare("SELECT SUM(amount) as month_total FROM expenses WHERE user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE())");
$stmt->execute([$_SESSION['user_id']]);
$month_total = $stmt->fetch(PDO::FETCH_ASSOC)['month_total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-brand">
            <i class="fas fa-wallet"></i>
            <span>Expense Tracker</span>
        </div>
        <div class="nav-user">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-info">
                    <h3>Total Expenses</h3>
                    <p>$<?php echo number_format($total, 2); ?></p>
                </div>
            </div>
            <div class="stat-card monthly">
                <div class="stat-icon"><i class="fas fa-calendar"></i></div>
                <div class="stat-info">
                    <h3>This Month</h3>
                    <p>$<?php echo number_format($month_total, 2); ?></p>
                </div>
            </div>
            <div class="stat-card categories">
                <div class="stat-icon"><i class="fas fa-tags"></i></div>
                <div class="stat-info">
                    <h3>Categories</h3>
                    <p><?php echo count($category_totals); ?></p>
                </div>
            </div>
        </div>

        <div class="main-content">
            <?php if (isset($_GET['error'])): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <div class="content-grid">
                <div class="expense-section">
                    <div class="section-header">
                        <h2>Add New Expense</h2>
                        <button class="toggle-form-btn" onclick="toggleExpenseForm()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <form id="expenseForm" action="add_expense.php" method="POST" class="expense-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="amount">Amount ($)</label>
                                <input type="number" id="amount" step="0.01" name="amount" required>
                            </div>
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Food">Food</option>
                                    <option value="Transportation">Transportation</option>
                                    <option value="Housing">Housing</option>
                                    <option value="Utilities">Utilities</option>
                                    <option value="Entertainment">Entertainment</option>
                                    <option value="Healthcare">Healthcare</option>
                                    <option value="Shopping">Shopping</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="description">Description</label>
                                <input type="text" id="description" name="description" required>
                            </div>
                            <div class="form-group">
                                <label for="date">Date</label>
                                <input type="date" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <button type="submit" class="submit-btn">
                            Add Expense
                        </button>
                    </form>
                </div>

                <div class="charts-section">
                    <div class="chart-container">
                        <h3>Expense Distribution</h3>
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3>Monthly Trends</h3>
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>

                <div class="recent-expenses">
                    <h2>Recent Transactions</h2>
                    <div class="transactions-list">
                        <?php if (empty($expenses)): ?>
                            <div class="no-expenses">No expenses recorded yet.</div>
                        <?php else: ?>
                            <?php foreach ($expenses as $expense): ?>
                                <div class="transaction-item">
                                    <div class="transaction-icon <?php echo strtolower($expense['category']); ?>">
                                        <i class="fas fa-<?php
                                            switch($expense['category']) {
                                                case 'Food': echo 'utensils'; break;
                                                case 'Transportation': echo 'car'; break;
                                                case 'Housing': echo 'home'; break;
                                                case 'Utilities': echo 'bolt'; break;
                                                case 'Entertainment': echo 'film'; break;
                                                case 'Healthcare': echo 'heart'; break;
                                                case 'Shopping': echo 'shopping-bag'; break;
                                                default: echo 'receipt';
                                            }
                                        ?>"></i>
                                    </div>
                                    <div class="transaction-info">
                                        <div class="transaction-title">
                                            <h4><?php echo htmlspecialchars($expense['description']); ?></h4>
                                            <span class="category-tag"><?php echo htmlspecialchars($expense['category']); ?></span>
                                        </div>
                                        <div class="transaction-meta">
                                            <span class="date"><?php echo date('M d, Y', strtotime($expense['date'])); ?></span>
                                            <span class="amount">$<?php echo number_format($expense['amount'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle expense form
        function toggleExpenseForm() {
            const form = document.getElementById('expenseForm');
            form.classList.toggle('show');
        }

        // Category Chart
        const categoryData = <?php echo json_encode(array_map(function($item) {
            return ['category' => $item['category'], 'total' => floatval($item['category_total'])];
        }, $category_totals)); ?>;

        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: categoryData.map(item => item.category),
                datasets: [{
                    data: categoryData.map(item => item.total),
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 20
                        }
                    }
                },
                cutout: '70%'
            }
        });

        // Monthly Chart
        const monthlyData = <?php echo json_encode(array_map(function($item) {
            return ['month' => $item['month'], 'total' => floatval($item['monthly_total'])];
        }, $monthly_totals)); ?>;

        new Chart(document.getElementById('monthlyChart'), {
            type: 'bar',
            data: {
                labels: monthlyData.map(item => {
                    const [year, month] = item.month.split('-');
                    return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Monthly Expenses',
                    data: monthlyData.map(item => item.total),
                    backgroundColor: '#36A2EB',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html> 