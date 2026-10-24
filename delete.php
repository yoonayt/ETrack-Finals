<?php
require_once __DIR__ . '/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Expense.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$userId = (int) $_SESSION['user_id'];

if (!$id || $id < 1) {
    header('Location: index.php?error=invalid_expense');
    exit();
}

try {
    $expenseObj = new Expense($pdo);
    $expenseObj->deleteExpense($id, $userId);

    header('Location: index.php?success=expense_deleted');
    exit();
} catch (Throwable $e) {
    error_log('ETrack delete expense error: ' . $e->getMessage());
    header('Location: index.php?error=delete_failed');
    exit();
}
