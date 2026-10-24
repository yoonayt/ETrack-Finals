
<?php
require_once __DIR__ . '/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Expense.php';

$userId = (int) $_SESSION['user_id'];
$expenseObj = new Expense($pdo);
$keyword = trim($_GET['keyword'] ?? '');
$expenses = $expenseObj->searchExpenses($keyword, $userId);

function searchEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETrack - Search Expenses</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="page">

    <header class="topbar">
        <div>
            <h1>ETrack</h1>
            <p>Search your expenses</p>
        </div>

        <div>
            <span>
                Welcome, <?= searchEscape($_SESSION['full_name'] ?? 'User') ?>
            </span>
            <a href="logout.php" class="btn btn-cancel"
               onclick="return confirm('Are you sure you want to log out?');">
                Logout
            </a>
        </div>
    </header>

    <section class="toolbar">
        <form action="search.php" method="GET" class="search-form">
            <input
                type="text"
                name="keyword"
                placeholder="Search expenses..."
                value="<?= searchEscape($keyword) ?>"
            >
            <button type="submit" class="btn">Search</button>
        </form>

        <a href="index.php" class="btn btn-cancel">Back to Dashboard</a>
    </section>

    <section class="table-card">
        <div class="table-header">
            <h2>Search Results</h2>
            <span><?= count($expenses) ?> result(s)</span>
        </div>

        <?php if ($keyword !== ''): ?>
            <p>
                Results for:
                <strong><?= searchEscape($keyword) ?></strong>
            </p>
        <?php endif; ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="5" class="empty-state">
                            No matching expenses were found in your account.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?= searchEscape($expense['expensedate']) ?></td>
                            <td><?= searchEscape($expense['description']) ?></td>
                            <td>
                                <?= searchEscape($expense['category_name'] ?? 'Uncategorized') ?>
                            </td>
                            <td>₱<?= number_format((float) $expense['amount'], 2) ?></td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-edit"
                                    onclick="openEditModal(
                                        <?= (int) $expense['expense_id'] ?>,
                                        <?= searchEscape(json_encode((string) $expense['amount'])) ?>,
                                        <?= searchEscape(json_encode((string) $expense['expensedate'])) ?>,
                                        <?= searchEscape(json_encode((string) $expense['description'])) ?>,
                                        <?= searchEscape(json_encode((string) ($expense['category_name'] ?? ''))) ?>
                                    )"
                                >
                                    Edit
                                </button>

                                <a
                                    class="btn btn-delete"
                                    href="delete.php?id=<?= (int) $expense['expense_id'] ?>"
                                    onclick="return confirm('Delete this expense?');"
                                >
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<!-- Edit Expense Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Edit Expense</h2>
            <button type="button" class="modal-close" onclick="closeEditModal()">
                &times;
            </button>
        </div>

        <form id="editExpenseForm" method="POST">
            <div class="field">
                <label for="edit-amount">Amount</label>
                <input
                    type="number"
                    id="edit-amount"
                    name="amount"
                    min="0.01"
                    step="0.01"
                    required
                >
            </div>

            <div class="field">
                <label for="edit-date">Expense Date</label>
                <input type="date" id="edit-date" name="expensedate" required>
            </div>

            <div class="field">
                <label for="edit-description">Description</label>
                <input
                    type="text"
                    id="edit-description"
                    name="description"
                    maxlength="255"
                    required
                >
            </div>

            <div class="field">
                <label for="edit-category">Category</label>
                <input
                    type="text"
                    id="edit-category"
                    name="category_name"
                    maxlength="100"
                    required
                >
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-cancel" onclick="closeEditModal()">
                    Cancel
                </button>
                <button type="submit" class="btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    const editModal = document.getElementById('editModal');

    function openEditModal(id, amount, date, description, category) {
        document.getElementById('editExpenseForm').action =
            'edit.php?id=' + encodeURIComponent(id);

        document.getElementById('edit-amount').value = amount;
        document.getElementById('edit-date').value = date;
        document.getElementById('edit-description').value = description;
        document.getElementById('edit-category').value = category;

        editModal.style.display = 'flex';
    }

    function closeEditModal() {
        editModal.style.display = 'none';
    }

    window.addEventListener('click', function (event) {
        if (event.target === editModal) {
            closeEditModal();
        }
    });
</script>

</body>
</html>
