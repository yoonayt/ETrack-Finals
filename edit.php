<?php
require_once __DIR__ . '/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Expense.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php?error=invalid_expense');
    exit();
}

$amount = trim($_POST['amount'] ?? '');
$expensedate = trim($_POST['expensedate'] ?? '');
$description = trim($_POST['description'] ?? '');
$categoryName = trim($_POST['category_name'] ?? '');

if (
    $amount === '' ||
    !is_numeric($amount) ||
    (float) $amount <= 0 ||
    $expensedate === '' ||
    $description === '' ||
    $categoryName === ''
) {
    header('Location: index.php?error=invalid_expense');
    exit();
}

try {
    $expenseObj = new Expense($pdo);

    // Confirm that this expense belongs to the logged-in user.
    $existingExpense = $expenseObj->getExpenseById($id, $userId);

    if (!$existingExpense) {
        header('Location: index.php?error=expense_not_found');
        exit();
    }

    $categoryId = $expenseObj->getOrCreateCategoryId($categoryName);

    $expenseObj->updateExpense(
        $id,
        $amount,
        $expensedate,
        $description,
        $categoryId,
        $userId
    );

    header('Location: index.php?success=expense_updated');
    exit();
} catch (Throwable $e) {
    error_log('ETrack edit expense error: ' . $e->getMessage());
    header('Location: index.php?error=edit_failed');
    exit();
}
