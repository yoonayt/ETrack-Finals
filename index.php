<?php
require_once __DIR__ . '/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Expense.php';

$userId = (int) $_SESSION['user_id'];
$expenseObj = new Expense($pdo);
$expenses = $expenseObj->getExpenses($userId);

$totalExpenses = count($expenses);
$totalAmount = 0;
$monthAmount = 0;
$todayAmount = 0;
$categoryTotals = [];

$currentMonth = date('Y-m');
$today = date('Y-m-d');

foreach ($expenses as $expense) {
    $amount = (float) $expense['amount'];
    $date = (string) $expense['expensedate'];
    $category = $expense['category_name'] ?? 'Uncategorized';

    $totalAmount += $amount;

    if (substr($date, 0, 7) === $currentMonth) {
        $monthAmount += $amount;
    }

    if (substr($date, 0, 10) === $today) {
        $todayAmount += $amount;
    }

    if (!isset($categoryTotals[$category])) {
        $categoryTotals[$category] = 0;
    }

    $categoryTotals[$category] += $amount;
}

$highestCategory = 'None';
$highestCategoryAmount = 0;

if (!empty($categoryTotals)) {
    arsort($categoryTotals);
    $highestCategory = (string) array_key_first($categoryTotals);
    $highestCategoryAmount = (float) $categoryTotals[$highestCategory];
}

$recentExpenses = $expenses;

usort($recentExpenses, function ($a, $b) {
    return strcmp(
        (string) ($b['expensedate'] ?? ''),
        (string) ($a['expensedate'] ?? '')
    );
});

$recentExpenses = array_slice($recentExpenses, 0, 5);

function eTrackEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$successMessages = [
    'expense_added' => 'Expense added successfully.',
    'expense_updated' => 'Expense updated successfully.',
    'expense_deleted' => 'Expense deleted successfully.'
];

$errorMessages = [
    'invalid_expense' => 'Please enter valid expense details.',
    'expense_not_found' => 'That expense could not be found in your account.',
    'add_failed' => 'Unable to add the expense. Please try again.',
    'edit_failed' => 'Unable to update the expense. Please try again.',
    'delete_failed' => 'Unable to delete the expense. Please try again.'
];

$success = $successMessages[$_GET['success'] ?? ''] ?? '';
$error = $errorMessages[$_GET['error'] ?? ''] ?? '';

$monthName = date('F Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETrack - Home</title>
    <link rel="stylesheet" href="assets/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
        }

        .page {
            padding-top: 0;
        }

/* Main navigation */
.main-navbar {
    width: 100vw;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 14px 24px;
    margin-left: calc(-1 * (100vw - 100%) / 2);
    margin-bottom: 10px;
    background: #ffffff;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 0 12px 12px 0;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
}

        .navbar-brand {
            color: var(--primary-dark, #4338ca);
            font-size: 23px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .navbar-links {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 8px;
        }

        .navbar-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            border-radius: 8px;
            color: var(--text, #1f2937);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .navbar-links a:hover {
            background: var(--primary-light, #eef2ff);
            color: var(--primary-dark, #4338ca);
        }

        .navbar-links a.active {
            background: var(--primary-dark, #4338ca);
            color: #ffffff;
        }

        .navbar-links a.logout-link {
            background: #fee2e2;
            color: #b91c1c;
        }

        .navbar-links a.logout-link:hover {
            background: #fecaca;
        }

        /* Dashboard heading */
        .topbar {
            margin-bottom: 24px;
        }

        .topbar h1 {
            margin-top: 0;
        }

        .topbar p {
            margin-bottom: 0;
        }

        .welcome-text {
            margin-top: 12px;
            font-size: 14px;
            color: var(--text-muted, #6b7280);
        }

        .alert {
            margin-bottom: 16px;
            padding: 12px 16px;
            border-radius: 8px;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
        }

        .alert.error {
            background: #fee2e2;
            color: #b91c1c;
        }

        /* Home dashboard content */
        .home-section {
            margin-bottom: 24px;
        }

        .home-section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .home-section-heading h2 {
            margin: 0;
        }

        .home-section-heading a {
            color: var(--primary-dark, #4338ca);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .home-section-heading a:hover {
            text-decoration: underline;
        }

        .dashboard-card {
            background: #ffffff;
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
        }

        .insights-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .insight-label {
            color: var(--text-muted, #6b7280);
            font-size: 13px;
            margin: 0 0 10px;
        }

        .insight-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--text, #1f2937);
            overflow-wrap: anywhere;
            margin: 0 0 8px;
        }

        .insight-description {
            color: var(--text-muted, #6b7280);
            font-size: 13px;
            margin: 0;
        }

        .category-track {
            height: 8px;
            background: var(--primary-light, #eef2ff);
            border-radius: 20px;
            overflow: hidden;
            margin: 14px 0 8px;
        }

        .category-track-fill {
            height: 100%;
            background: var(--primary, #46e583);
            border-radius: 20px;
        }

        .recent-list {
            display: flex;
            flex-direction: column;
        }

        .recent-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 15px 0;
            border-bottom: 1px solid var(--border, #e5e7eb);
        }

        .recent-item:first-child {
            padding-top: 0;
        }

        .recent-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .recent-item-details {
            min-width: 0;
        }

        .recent-item-title {
            font-weight: 600;
            color: var(--text, #1f2937);
            overflow-wrap: anywhere;
            margin: 0 0 5px;
        }

        .recent-item-meta {
            color: var(--text-muted, #6b7280);
            font-size: 13px;
            margin: 0;
        }

        .recent-item-amount {
            font-weight: 700;
            white-space: nowrap;
            color: var(--text, #1f2937);
        }

        .empty-dashboard {
            padding: 24px 12px;
            text-align: center;
            color: var(--text-muted, #6b7280);
        }

        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .quick-actions .btn {
            text-decoration: none;
            text-align: center;
        }

        @media (max-width: 700px) {
            .main-navbar {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
                padding: 14px;
            }

            .navbar-links {
                width: 100%;
                justify-content: flex-start;
                gap: 6px;
            }

            .navbar-links a {
                padding: 9px 11px;
                font-size: 13px;
            }

            .insights-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-card {
                padding: 16px;
            }
        }

        @media (max-width: 420px) {
            .navbar-links {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .navbar-links a {
                width: 100%;
            }

            .recent-item {
                align-items: flex-start;
            }

            .recent-item-amount {
                font-size: 14px;
            }
        }

        
/* Add Expense Modal Background */
#addModal .modal {
    background: #ffffff;
    color: #1f2937;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 480px;
}

/* Dim the background behind the modal */
#addModal.modal-overlay {
    background: rgba(0, 0, 0, 0.55);
    position: fixed;
    inset: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow-y: auto;
}

/* Ensure form fields are readable */
#addModal .field label {
    display: block;
    margin-bottom: 6px;
    color: #374151;
}

#addModal .field input {
    width: 100%;
    background: #ffffff;
    color: #1f2937;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 10px 12px;
}

/* Modal heading */
#addModal .modal-header h2 {
    color: #1f2937;
    margin-top: 0;
}

    </style>
</head>

<body>

<div class="page">

    <!-- Top Navigation Bar -->
    <nav class="main-navbar" aria-label="Main navigation">
        <a href="index.php" class="navbar-brand">💰 ETrack</a>

        <div class="navbar-links">
            <a href="index.php" class="active" aria-current="page">
                Home
            </a>

            <a href="transactions.php">
                Transactions
            </a>

            <a href="sessions.php">
                Sessions
            </a>

            <a
                href="logout.php"
                class="logout-link"
                onclick="return confirm('Are you sure you want to log out?');"
            >
                Logout
            </a>
        </div>
    </nav>

    <!-- Home Welcome -->
    <header class="topbar">
        <div>
            <h1>Dashboard</h1>
            <p>Here's an overview of your expenses and spending activity.</p>

            <div class="welcome-text">
                Welcome back,
                <?= eTrackEscape($_SESSION['full_name'] ?? 'User') ?>!
                Today is <?= date('F j, Y') ?>.
            </div>
        </div>
    </header>

    <?php if ($success !== ''): ?>
        <div class="alert success">
            <?= eTrackEscape($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert error">
            <?= eTrackEscape($error) ?>
        </div>
    <?php endif; ?>

    <!-- Financial Overview -->
    <section class="stats">
        <div class="stat-card">
            <h3>Total Expenses</h3>
            <p>₱<?= number_format($totalAmount, 2) ?></p>
            <small>Across all recorded expenses</small>
        </div>

        <div class="stat-card">
            <h3>This Month</h3>
            <p>₱<?= number_format($monthAmount, 2) ?></p>
            <small><?= eTrackEscape($monthName) ?></small>
        </div>

        <div class="stat-card">
            <h3>Today</h3>
            <p>₱<?= number_format($todayAmount, 2) ?></p>
            <small><?= $todayAmount > 0 ? 'Spent today' : 'No expenses recorded today' ?></small>
        </div>
    </section>

    <!-- Spending Insights -->
    <section class="home-section">
        <div class="home-section-heading">
            <h2>Spending Insights</h2>
        </div>

        <div class="insights-grid">
            <div class="dashboard-card">
                <p class="insight-label">Highest Spending Category</p>

                <p class="insight-value">
                    <?= eTrackEscape($highestCategory) ?>
                </p>

                <p class="insight-description">
                    ₱<?= number_format($highestCategoryAmount, 2) ?>
                    spent in this category across all recorded expenses.
                </p>

                <?php
                $categoryPercentage = $totalAmount > 0
                    ? ($highestCategoryAmount / $totalAmount) * 100
                    : 0;
                ?>

                <div
                    class="category-track"
                    role="progressbar"
                    aria-label="Share of expenses in highest spending category"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="<?= (int) round($categoryPercentage) ?>"
                >
                    <div
                        class="category-track-fill"
                        style="width: <?= min(100, max(0, $categoryPercentage)) ?>%;"
                    ></div>
                </div>

                <p class="insight-description">
                    <?= number_format($categoryPercentage, 1) ?>%
                    of your total recorded spending.
                </p>
            </div>

            <div class="dashboard-card">
                <p class="insight-label">Expense Activity</p>

                <p class="insight-value">
                    <?= number_format($totalExpenses) ?>
                    <?= $totalExpenses === 1 ? 'record' : 'records' ?>
                </p>

                <p class="insight-description">
                    You have recorded
                    <?= number_format($totalExpenses) ?>
                    <?= $totalExpenses === 1 ? 'expense' : 'expenses' ?>
                    with a combined value of
                    ₱<?= number_format($totalAmount, 2) ?>.
                </p>
            </div>
        </div>
    </section>

 
    <!-- Recent Activity -->
    <section class="home-section">
        <div class="home-section-heading">
            <h2>Recent Activity</h2>

            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <button
                    type="button"
                    class="btn"
                    onclick="openAddModal()"
                >
                    + Add Expense
                </button>

                <a href="transactions.php">View All Transactions &rarr;</a>
            </div>
        </div>

        <div class="dashboard-card">
            <?php if (empty($recentExpenses)): ?>
                <div class="empty-dashboard">
                    <p><strong>No expenses yet</strong></p>
                    <p>Add your first expense to see your activity here.</p>
                </div>
            <?php else: ?>
                <div class="recent-list">
                    <?php foreach ($recentExpenses as $expense): ?>
                        <div class="recent-item">
                            <div class="recent-item-details">
                                <p class="recent-item-title">
                                    <?= eTrackEscape($expense['description']) ?>
                                </p>

                                <p class="recent-item-meta">
                                    <?= eTrackEscape($expense['category_name'] ?? 'Uncategorized') ?>
                                    &middot;
                                    <?= eTrackEscape($expense['expensedate']) ?>
                                </p>
                            </div>

                            <div class="recent-item-amount">
                                ₱<?= number_format((float) $expense['amount'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>


<!-- Add Expense Modal: existing form preserved -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Add Expense</h2>

            <button
                type="button"
                class="modal-close"
                onclick="closeAddModal()"
            >
                &times;
            </button>
        </div>

        <form action="add.php" method="POST">
            <div class="field">
                <label for="add-amount">Amount</label>
                <input
                    type="number"
                    id="add-amount"
                    name="amount"
                    min="0.01"
                    step="0.01"
                    required
                >
            </div>

            <div class="field">
                <label for="add-date">Expense Date</label>
                <input
                    type="date"
                    id="add-date"
                    name="expensedate"
                    value="<?= date('Y-m-d') ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="add-description">Description</label>
                <input
                    type="text"
                    id="add-description"
                    name="description"
                    maxlength="255"
                    required
                >
            </div>

            <div class="field">
                <label for="add-category">Category</label>
                <input
                    type="text"
                    id="add-category"
                    name="category_name"
                    maxlength="100"
                    placeholder="e.g. Food, Transportation"
                    required
                >
            </div>

            <div class="modal-actions">
                <button
                    type="button"
                    class="btn btn-cancel"
                    onclick="closeAddModal()"
                >
                    Cancel
                </button>

                <button type="submit" class="btn">
                    Save Expense
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const addModal = document.getElementById('addModal');

    function openAddModal() {
        addModal.style.display = 'flex';
    }

    function closeAddModal() {
        addModal.style.display = 'none';
    }

    window.addEventListener('click', function (event) {
        if (event.target === addModal) {
            closeAddModal();
        }
    });

    window.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAddModal();
        }
    });
</script>

</body>
</html>