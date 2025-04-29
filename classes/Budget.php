<?php
class Budget {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Add a budget
    public function addBudget($userId, $category, $limitAmount, $startDate, $endDate) {
        $stmt = $this->pdo->prepare("INSERT INTO budgets (user_id, category, limit_amount, start_date, end_date) 
                                     VALUES (:user_id, :category, :limit_amount, :start_date, :end_date)");
        return $stmt->execute([
            'user_id' => $userId,
            'category' => $category,
            'limit_amount' => $limitAmount,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    // Get all budgets for a user
    public function getBudgets($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM budgets WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Check budget alerts
    public function checkBudgetAlerts($userId) {
        $stmt = $this->pdo->prepare("SELECT b.category, SUM(t.amount) as spent, b.limit_amount 
                                     FROM budgets b 
                                     LEFT JOIN transactions t ON b.user_id = t.user_id AND b.category = t.category 
                                     WHERE b.user_id = :user_id AND t.date BETWEEN b.start_date AND b.end_date 
                                     GROUP BY b.category 
                                     HAVING spent > b.limit_amount");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add a notification
    public function addNotification($userId, $message) {
        $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)");
        return $stmt->execute(['user_id' => $userId, 'message' => $message]);
    }
    // Get budget progress
    public function getBudgetProgress($userId) {
        $stmt = $this->pdo->prepare("SELECT b.category, b.limit_amount, SUM(t.amount) as spent 
                                     FROM budgets b 
                                     LEFT JOIN transactions t ON b.user_id = t.user_id AND b.category = t.category 
                                     WHERE b.user_id = :user_id AND t.date BETWEEN b.start_date AND b.end_date 
                                     GROUP BY b.category");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
        // Edit a budget
        public function editBudget($budgetId, $category, $limitAmount, $startDate, $endDate) {
            $stmt = $this->pdo->prepare("UPDATE budgets SET category = :category, limit_amount = :limit_amount, start_date = :start_date, end_date = :end_date WHERE id = :id");
            return $stmt->execute([
                'category' => $category,
                'limit_amount' => $limitAmount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'id' => $budgetId
            ]);
        }
    
        // Delete a budget
        public function deleteBudget($budgetId) {
            $stmt = $this->pdo->prepare("DELETE FROM budgets WHERE id = :id");
            return $stmt->execute(['id' => $budgetId]);
        }
}
?>