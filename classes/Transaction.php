<?php
require_once __DIR__ . '/Budget.php'; // Include the Budget class

class Transaction {
    private $pdo;
    private $budget; // Add a reference to the Budget class

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->budget = new Budget($pdo); // Initialize the Budget class
    }

    // Add a transaction (updated to use category_id)
    public function addTransaction($userId, $type, $amount, $categoryId, $date, $notes, $isRecurring, $frequency = null) {
        $stmt = $this->pdo->prepare("INSERT INTO transactions (user_id, type, amount, category_id, date, notes, is_recurring, frequency) 
                                     VALUES (:user_id, :type, :amount, :category_id, :date, :notes, :is_recurring, :frequency)");
        $success = $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'category_id' => $categoryId,
            'date' => $date,
            'notes' => $notes,
            'is_recurring' => $isRecurring,
            'frequency' => $frequency
        ]);

        if ($success) {
            // Check for budget alerts after adding the transaction
            $this->budget->checkBudgetAlerts($userId);
        }

        return $success;
    }

    // Get all transactions for a user (updated to join categories table)
    public function getTransactions($userId) {
        $query = "SELECT t.id, t.type, t.amount, c.name AS category_name, t.date, t.notes, t.is_recurring, t.frequency 
                  FROM transactions t 
                  LEFT JOIN categories c ON t.category_id = c.id 
                  WHERE t.user_id = :user_id 
                  ORDER BY t.date DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get recurring transactions for a user
    public function getRecurringTransactions($userId) {
        $query = "SELECT t.id, t.type, t.amount, c.name AS category_name, t.date, t.notes, t.frequency 
                  FROM transactions t 
                  LEFT JOIN categories c ON t.category_id = c.id 
                  WHERE t.user_id = :user_id AND t.is_recurring = 1";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Generate future recurring transactions
    public function generateFutureRecurringTransactions($userId, $endDate) {
        $recurringTransactions = $this->getRecurringTransactions($userId);
        $futureTransactions = [];

        foreach ($recurringTransactions as $transaction) {
            $startDate = new DateTime($transaction['date']);
            $endDateObj = new DateTime($endDate);

            while ($startDate <= $endDateObj) {
                $futureTransactions[] = [
                    'type' => $transaction['type'],
                    'amount' => $transaction['amount'],
                    'category_name' => $transaction['category_name'], // Use category_name
                    'date' => $startDate->format('Y-m-d'),
                    'notes' => $transaction['notes']
                ];

                if ($transaction['frequency'] === 'monthly') {
                    $startDate->modify('+1 month');
                } elseif ($transaction['frequency'] === 'yearly') {
                    $startDate->modify('+1 year');
                }
            }
        }

        return $futureTransactions;
    }

    // Generate monthly summary
    public function getMonthlySummary($userId, $year, $month) {
        $stmt = $this->pdo->prepare("SELECT SUM(amount) as total, type FROM transactions 
                                     WHERE user_id = :user_id AND YEAR(date) = :year AND MONTH(date) = :month 
                                     GROUP BY type");
        $stmt->execute(['user_id' => $userId, 'year' => $year, 'month' => $month]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Edit a transaction (updated to use category_id)
    public function editTransaction($transactionId, $type, $amount, $categoryId, $date, $notes, $isRecurring, $frequency = null) {
        $stmt = $this->pdo->prepare("UPDATE transactions 
                                     SET type = :type, amount = :amount, category_id = :category_id, date = :date, notes = :notes, is_recurring = :is_recurring, frequency = :frequency 
                                     WHERE id = :id");
        $success = $stmt->execute([
            'type' => $type,
            'amount' => $amount,
            'category_id' => $categoryId,
            'date' => $date,
            'notes' => $notes,
            'is_recurring' => $isRecurring,
            'frequency' => $frequency,
            'id' => $transactionId
        ]);

        if ($success) {
            // Retrieve the user ID of the transaction being edited
            $stmt = $this->pdo->prepare("SELECT user_id FROM transactions WHERE id = :id");
            $stmt->execute(['id' => $transactionId]);
            $userId = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'];

            // Check for budget alerts after editing the transaction
            $this->budget->checkBudgetAlerts($userId);
        }

        return $success;
    }

    // Delete a transaction
    public function deleteTransaction($transactionId) {
        $stmt = $this->pdo->prepare("DELETE FROM transactions WHERE id = :id");
        return $stmt->execute(['id' => $transactionId]);
    }
}
?>