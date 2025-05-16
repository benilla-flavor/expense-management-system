<?php
class Budget {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Add a budget
    public function addBudget($userId, $categoryId, $limitAmount, $startDate, $endDate) {
        $stmt = $this->pdo->prepare("INSERT INTO budgets (user_id, category_id, limit_amount, start_date, end_date) 
                                     VALUES (:user_id, :category_id, :limit_amount, :start_date, :end_date)");
        return $stmt->execute([
            'user_id' => $userId,
            'category_id' => $categoryId,
            'limit_amount' => $limitAmount,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    // Get all budgets for a user
    public function getBudgets($userId) {
        $query = "SELECT b.id, c.name AS category_name, b.limit_amount, b.start_date, b.end_date, COALESCE(SUM(t.amount), 0) AS spent 
                  FROM budgets b 
                  INNER JOIN categories c ON b.category_id = c.id 
                  LEFT JOIN transactions t ON b.user_id = t.user_id AND b.category_id = t.category_id 
                  WHERE b.user_id = :user_id 
                  GROUP BY b.id, c.name";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check budget alerts (prevent duplicates)
    public function checkBudgetAlerts($userId) {
        $alerts = [];

        // Fetch budgets and related spending data
        $query = "SELECT b.category_id, c.name AS category_name, b.limit_amount, COALESCE(SUM(t.amount), 0) AS spent
                  FROM budgets b 
                  INNER JOIN categories c ON b.category_id = c.id 
                  LEFT JOIN transactions t ON b.user_id = t.user_id AND b.category_id = t.category_id 
                  WHERE b.user_id = :user_id AND t.date BETWEEN b.start_date AND b.end_date 
                  GROUP BY b.category_id, c.name, b.limit_amount";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($budgets as $budget) {
            if ($budget['spent'] > $budget['limit_amount']) {
                // Construct the exact message string
                $message = "You have exceeded your budget for {$budget['category_name']}. Spent: \${$budget['spent']}, Limit: \${$budget['limit_amount']}";

                // Check if a notification for this message already exists
                $notificationStmt = $this->pdo->prepare("
                    SELECT COUNT(*) AS count
                    FROM notifications
                    WHERE user_id = :user_id AND message = :message AND is_read = 0
                ");
                $notificationStmt->execute([
                    'user_id' => $userId,
                    'message' => $message
                ]);
                $notificationCount = $notificationStmt->fetch(PDO::FETCH_ASSOC)['count'];

                if ($notificationCount === 0) {
                    // Only add the alert if no unread notification exists for this message
                    $alerts[] = [
                        'category_name' => $budget['category_name'],
                        'spent' => $budget['spent'],
                        'limit_amount' => $budget['limit_amount']
                    ];

                    // Add the notification
                    $this->addNotification($userId, $message);
                }
            }
        }

        return $alerts;
    }

    // Add a notification (use INSERT IGNORE to prevent duplicates due to race conditions)
    public function addNotification($userId, $message) {
        try {
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO notifications (user_id, message, is_read, created_at) 
                                         VALUES (:user_id, :message, 0, NOW())");
            return $stmt->execute(['user_id' => $userId, 'message' => $message]);
        } catch (PDOException $e) {
            error_log("Failed to add notification: " . $e->getMessage());
            return false;
        }
    }

    // Delete all notifications for a user
    public function clearAllNotifications($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM notifications WHERE user_id = :user_id");
        return $stmt->execute(['user_id' => $userId]);
    }

    // Get budget progress
    public function getBudgetProgress($userId) {
        $query = "SELECT c.name AS category_name, b.limit_amount, SUM(t.amount) AS spent 
                  FROM budgets b 
                  INNER JOIN categories c ON b.category_id = c.id 
                  LEFT JOIN transactions t ON b.user_id = t.user_id AND b.category_id = t.category_id 
                  WHERE b.user_id = :user_id 
                  GROUP BY c.name";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Edit a budget
    public function editBudget($budgetId, $categoryId, $limitAmount, $startDate, $endDate) {
        $stmt = $this->pdo->prepare("UPDATE budgets 
                                     SET category_id = :category_id, limit_amount = :limit_amount, start_date = :start_date, end_date = :end_date 
                                     WHERE id = :id");
        return $stmt->execute([
            'category_id' => $categoryId,
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