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
    $categoryId = $expenseObj->getOrCreateCategoryId($categoryName);

    $expenseObj->addExpense(
        $amount,
        $expensedate,
        $description,
        $categoryId,
        $userId
    );

    header('Location: index.php?success=expense_added');
    exit();
} catch (Throwable $e) {
    error_log('ETrack add expense error: ' . $e->getMessage());
    header('Location: index.php?error=add_failed');
    exit();
}
