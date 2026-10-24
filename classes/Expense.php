<?php

class Expense
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Add an expense for the logged-in user.
     */
    public function addExpense(
        $amount,
        $expensedate,
        $description,
        $category_id,
        $user_id
    ): bool {
        $sql = "INSERT INTO expenses
                    (amount, expensedate, description, category_id, user_id)
                VALUES
                    (:amount, :expensedate, :description, :category_id, :user_id)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':amount' => $amount,
            ':expensedate' => $expensedate,
            ':description' => $description,
            ':category_id' => $category_id,
            ':user_id' => (int) $user_id
        ]);
    }

    /**
     * Retrieve only the logged-in user's expenses.
     */
    public function getExpenses($user_id): array
    {
        $sql = "SELECT
                    e.*,
                    c.category_name
                FROM expenses e
                LEFT JOIN categories c
                    ON e.category_id = c.category_id
                WHERE e.user_id = :user_id
                ORDER BY e.expensedate DESC, e.expense_id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':user_id' => (int) $user_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve one expense only if it belongs to the logged-in user.
     */
    public function getExpenseById($id, $user_id): array|false
    {
        $sql = "SELECT
                    e.*,
                    c.category_name
                FROM expenses e
                LEFT JOIN categories c
                    ON e.category_id = c.category_id
                WHERE e.expense_id = :id
                  AND e.user_id = :user_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => (int) $id,
            ':user_id' => (int) $user_id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update an expense only if it belongs to the logged-in user.
     */
    public function updateExpense(
        $id,
        $amount,
        $expensedate,
        $description,
        $category_id,
        $user_id
    ): bool {
        $sql = "UPDATE expenses
                SET amount = :amount,
                    expensedate = :expensedate,
                    description = :description,
                    category_id = :category_id
                WHERE expense_id = :id
                  AND user_id = :user_id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':amount' => $amount,
            ':expensedate' => $expensedate,
            ':description' => $description,
            ':category_id' => (int) $category_id,
            ':id' => (int) $id,
            ':user_id' => (int) $user_id
        ]);
    }

    /**
     * Delete an expense only if it belongs to the logged-in user.
     */
    public function deleteExpense($id, $user_id): bool
    {
        $sql = "DELETE FROM expenses
                WHERE expense_id = :id
                  AND user_id = :user_id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':id' => (int) $id,
            ':user_id' => (int) $user_id
        ]);
    }

    /**
     * Search only the logged-in user's expenses.
     */
    public function searchExpenses($keyword, $user_id): array
    {
        $sql = "SELECT
                    e.*,
                    c.category_name
                FROM expenses e
                LEFT JOIN categories c
                    ON e.category_id = c.category_id
                WHERE e.user_id = :user_id
                  AND (
                      e.description LIKE :keyword
                      OR c.category_name LIKE :keyword
                      OR CAST(e.amount AS CHAR) LIKE :keyword
                      OR CAST(e.expensedate AS CHAR) LIKE :keyword
                  )
                ORDER BY e.expensedate DESC, e.expense_id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':user_id' => (int) $user_id,
            ':keyword' => '%' . $keyword . '%'
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find an existing category or create it if it doesn't exist.
     * Categories remain shared across users.
     */
    public function getOrCreateCategoryId($categoryName): int
    {
        $categoryName = trim((string) $categoryName);

        if ($categoryName === '') {
            throw new InvalidArgumentException('Category is required.');
        }

        $sql = "SELECT category_id
                FROM categories
                WHERE category_name = :category_name
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':category_name' => $categoryName
        ]);

        $categoryId = $stmt->fetchColumn();

        if ($categoryId !== false) {
            return (int) $categoryId;
        }

        $insertSql = "INSERT INTO categories (category_name)
                      VALUES (:category_name)";

        $insertStmt = $this->conn->prepare($insertSql);
        $insertStmt->execute([
            ':category_name' => $categoryName
        ]);

        return (int) $this->conn->lastInsertId();
    }
}
