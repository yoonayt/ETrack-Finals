<?php
require_once __DIR__ . '/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Expense.php';

$userId = (int) $_SESSION['user_id'];

$expenseObj = new Expense($pdo);
$expenses = $expenseObj->getExpenses($userId);

function transactionEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$totalTransactions = count($expenses);
$totalAmount = 0;
$monthlyAmount = 0;
$todayAmount = 0;

$today = date('Y-m-d');
$currentMonth = date('Y-m');

foreach ($expenses as $expense) {
    $amount = (float) $expense['amount'];
    $date = (string) $expense['expensedate'];

    $totalAmount += $amount;

    if (substr($date, 0, 7) === $currentMonth) {
        $monthlyAmount += $amount;
    }

    if (substr($date, 0, 10) === $today) {
        $todayAmount += $amount;
    }
}

/* Search transactions belonging to the logged-in user. */
$keyword = trim($_GET['keyword'] ?? '');

if ($keyword !== '') {
    $expenses = array_values(array_filter(
        $expenses,
        function ($expense) use ($keyword) {
            $searchable = implode(' ', [
                $expense['description'] ?? '',
                $expense['category_name'] ?? '',
                $expense['expensedate'] ?? '',
                $expense['amount'] ?? ''
            ]);

            return stripos($searchable, $keyword) !== false;
        }
    ));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETrack - Transactions</title>
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

        /* Same main navigation as Home */
        .main-navbar {
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 14px 24px;
            margin-left: calc(-1 * (100vw - 100%) / 2);
            margin-bottom: 0px;
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

        .transactions-page {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px 48px;
        }

        .transaction-heading {
            margin-bottom: 24px;
        }

        .transaction-heading h2 {
            margin: 0 0 7px;
            color: #111827;
            font-size: 27px;
        }

        .transaction-heading p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
        }

        .transaction-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 26px;
        }

        .transaction-stat {
            padding: 22px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
        }

        .transaction-stat-label {
            margin-bottom: 10px;
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .transaction-stat-value {
            color: #111827;
            font-size: clamp(21px, 3vw, 29px);
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .transaction-panel {
            overflow: hidden;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
        }

        .transaction-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            padding: 22px;
            border-bottom: 1px solid #e5e7eb;
        }

        .transaction-panel-header h3 {
            margin: 0 0 5px;
            color: #111827;
            font-size: 18px;
        }

        .transaction-panel-header p {
            margin: 0;
            color: #6b7280;
            font-size: 13px;
        }

        .transaction-search {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            width: 100%;
            max-width: 460px;
        }

        .transaction-search input {
            flex: 1;
            min-width: 160px;
            padding: 11px 13px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }

        .transaction-search input:focus {
            outline: none;
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .12);
        }

        .transaction-search button,
        .transaction-search .clear-search {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            border: 0;
            border-radius: 8px;
            background: #16a34a;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .transaction-search .clear-search {
            background: #f3f4f6;
            color: #374151;
        }

        .transaction-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .transaction-table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
        }

        .transaction-table th {
            padding: 14px 18px;
            background: #f9fafb;
            color: #6b7280;
            text-align: left;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
            white-space: nowrap;
            border-bottom: 1px solid #e5e7eb;
        }

        .transaction-table td {
            padding: 17px 18px;
            color: #374151;
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f2f5;
        }

        .transaction-table tbody tr:hover {
            background: #f9fafb;
        }

        .transaction-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .transaction-description {
            color: #111827;
            font-weight: 700;
        }

        .transaction-category {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #ecfdf3;
            color: #15803d;
            font-size: 11px;
            font-weight: 700;
        }

        .transaction-amount {
            color: #111827;
            font-weight: 800;
            white-space: nowrap;
        }

        .transaction-empty {
            padding: 48px 20px !important;
            text-align: center;
            color: #6b7280 !important;
            line-height: 1.8;
        }

        .transaction-footer {
            padding: 16px 22px;
            color: #6b7280;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 760px) {
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

            .transactions-page {
                padding: 0 14px 36px;
            }

            .transaction-stats {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .transaction-stat {
                padding: 18px;
            }

            .transaction-panel-header {
                align-items: stretch;
                padding: 18px;
            }

            .transaction-search {
                max-width: none;
            }

            .transaction-heading h2 {
                font-size: 23px;
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
        }
    </style>
</head>

<body>

<div class="page">

    <!-- Same navigation markup as Home -->
    <nav class="main-navbar" aria-label="Main navigation">
        <a href="index.php" class="navbar-brand">💰 ETrack</a>

        <div class="navbar-links">
            <a href="index.php">
                Home
            </a>

            <a href="transactions.php" class="active" aria-current="page">
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

</div>

<main class="transactions-page">

    <section class="transaction-heading">
        <h2>Transactions</h2>
        <p>Review and search your recorded expenses in one place.</p>
    </section>

    <section class="transaction-stats">
        <div class="transaction-stat">
            <div class="transaction-stat-label">Total Transactions</div>
            <div class="transaction-stat-value">
                <?= number_format($totalTransactions) ?>
            </div>
        </div>

        <div class="transaction-stat">
            <div class="transaction-stat-label">Total Amount</div>
            <div class="transaction-stat-value">
                ₱<?= number_format($totalAmount, 2) ?>
            </div>
        </div>

        <div class="transaction-stat">
            <div class="transaction-stat-label">This Month</div>
            <div class="transaction-stat-value">
                ₱<?= number_format($monthlyAmount, 2) ?>
            </div>
        </div>
    </section>

    <section class="transaction-panel">
        <div class="transaction-panel-header">
            <div>
                <h3>Transaction History</h3>
                <p>
                    <?= number_format(count($expenses)) ?>
                    result(s) displayed
                </p>
            </div>

            <form class="transaction-search" method="GET"
                  action="transactions.php">
                <input
                    type="search"
                    name="keyword"
                    placeholder="Search description, category, date..."
                    value="<?= transactionEscape($keyword) ?>"
                    aria-label="Search transactions"
                >

                <button type="submit">Search</button>

                <?php if ($keyword !== ''): ?>
                    <a class="clear-search" href="transactions.php">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="transaction-table-wrap">
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Amount</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="4" class="transaction-empty">
                            <?php if ($keyword !== ''): ?>
                                No transactions match
                                "<?= transactionEscape($keyword) ?>".
                            <?php else: ?>
                                No transactions recorded yet.<br>
                                Add an expense from the Home page to see it here.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td>
                                <?= transactionEscape($expense['expensedate'] ?? '') ?>
                            </td>

                            <td>
                                <span class="transaction-description">
                                    <?= transactionEscape($expense['description'] ?? '') ?>
                                </span>
                            </td>

                            <td>
                                <span class="transaction-category">
                                    <?= transactionEscape(
                                        $expense['category_name'] ?? 'Uncategorized'
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <span class="transaction-amount">
                                    ₱<?= number_format(
                                        (float) ($expense['amount'] ?? 0),
                                        2
                                    ) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="transaction-footer">
            Transaction history is based on the expenses saved in your account.
        </div>
    </section>

</main>

</body>
</html>