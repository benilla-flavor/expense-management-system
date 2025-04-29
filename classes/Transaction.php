<?php
class Transaction {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Add a transaction (updated to include frequency)
    public function addTransaction($userId, $type, $amount, $category, $date, $notes, $isRecurring, $frequency = null) {
        $stmt = $this->pdo->prepare("INSERT INTO transactions (user_id, type, amount, category, date, notes, is_recurring, frequency) 
                                     VALUES (:user_id, :type, :amount, :category, :date, :notes, :is_recurring, :frequency)");
        return $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'category' => $category,
            'date' => $date,
            'notes' => $notes,
            'is_recurring' => $isRecurring,
            'frequency' => $frequency
        ]);
    }
    // Get all transactions for a user
    public function getTransactions($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE user_id = :user_id ORDER BY date DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Get recurring transactions for a user
    public function getRecurringTransactions($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE user_id = :user_id AND is_recurring = 1");
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
                    'category' => $transaction['category'],
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

        // Bulk import transactions from CSV
        public function importTransactions($userId, $file) {
            if (($handle = fopen($file, "r")) !== FALSE) {
                $header = fgetcsv($handle); // Skip header row
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $type = $data[0];
                    $amount = $data[1];
                    $category = $data[2];
                    $date = $data[3];
                    $notes = $data[4] ?? '';
                    $isRecurring = $data[5] ?? 0;
                    $frequency = $data[6] ?? null;
    
                    $this->addTransaction($userId, $type, $amount, $category, $date, $notes, $isRecurring, $frequency);
                }
                fclose($handle);
                return true;
            }
            return false;
        }
    
        // Export transactions to CSV
        public function exportTransactions($userId) {
            $transactions = $this->getTransactions($userId);
    
            $output = fopen('php://output', 'w');
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="transactions.csv"');
    
            fputcsv($output, ['Type', 'Amount', 'Category', 'Date', 'Notes', 'Is Recurring', 'Frequency']);
    
            foreach ($transactions as $t) {
                fputcsv($output, [
                    $t['type'],
                    $t['amount'],
                    $t['category'],
                    $t['date'],
                    $t['notes'],
                    $t['is_recurring'],
                    $t['frequency']
                ]);
            }
    
            fclose($output);
            exit;
        }

        // Generate monthly summary
        public function getMonthlySummary($userId, $year, $month) {
            $stmt = $this->pdo->prepare("SELECT SUM(amount) as total, type FROM transactions 
                                         WHERE user_id = :user_id AND YEAR(date) = :year AND MONTH(date) = :month 
                                         GROUP BY type");
            $stmt->execute(['user_id' => $userId, 'year' => $year, 'month' => $month]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        // Edit a transaction
        public function editTransaction($transactionId, $type, $amount, $category, $date, $notes, $isRecurring, $frequency = null) {
            $stmt = $this->pdo->prepare("UPDATE transactions SET type = :type, amount = :amount, category = :category, date = :date, notes = :notes, is_recurring = :is_recurring, frequency = :frequency WHERE id = :id");
            return $stmt->execute([
                'type' => $type,
                'amount' => $amount,
                'category' => $category,
                'date' => $date,
                'notes' => $notes,
                'is_recurring' => $isRecurring,
                'frequency' => $frequency,
                'id' => $transactionId
            ]);
        }
    
        // Delete a transaction
        public function deleteTransaction($transactionId) {
            $stmt = $this->pdo->prepare("DELETE FROM transactions WHERE id = :id");
            return $stmt->execute(['id' => $transactionId]);
        }
}
?>