<?php


namespace model;


use model\accounts\Account;
use model\accounts\AccountDAO;
use model\categories\CategoryDAO;
use model\transactions\Transaction;

class StatisticDAO {

    public function getTransactionsSum($user_id, Account $account = null, $from_date = null, $to_date = null) {
        $instance = Connection::getInstance();
        $conn = $instance->getConn();

        $params = [];
        $params[] = $user_id;
        $sql = "SELECT t.id, t.amount, t.currency, t.account_id, t.category_id, t.note, t.time_event FROM transactions t
                JOIN transaction_categories tc ON tc.id = t.category_id
                JOIN accounts a ON a.id = t.account_id
                WHERE tc.type IS NOT NULL AND a.owner_id = ? ";
        if ($from_date && $to_date) {
            $sql .= 'AND t.time_event BETWEEN ? AND ? + INTERVAL 1 DAY ';
            $params[] = $from_date;
            $params[] = $to_date;
        }
        if ($account) {
            $sql .= 'AND a.id = ? ';
            $params[] = $account->getId();
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $transactions = [];
        $accountDAO = new AccountDAO();
        $categoryDAO = new CategoryDAO();
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) {
            $transaction = new Transaction(
                $row->amount,
                $accountDAO->getAccountById($row->account_id),
                $row->currency,
                $categoryDAO->getCategoryById($row->category_id, $_SESSION['logged_user']),
                $row->note,
                $row->time_event
            );
            $transaction->setId($row->id);
            $transactions[] = $transaction;
        }

        return $transactions;
    }

    public function getTransactionsByCategory($user_id, $from_date = null, $to_date = null) {
        $instance = Connection::getInstance();
        $conn = $instance->getConn();
        $params = [];
        $params[] = $user_id;
        $sql = "SELECT tc.name AS category_name, tc.type, COALESCE(SUM(t.amount), 0) AS amount FROM transactions AS t
                JOIN accounts AS a ON(a.id = t.account_id)
                JOIN transaction_categories AS tc ON(t.category_id = tc.id)
                WHERE a.owner_id = ? AND tc.type IS NOT NULL ";
        if ($from_date && $to_date) {
            $sql .= "AND t.time_event BETWEEN ? AND ? + INTERVAL 1 DAY ";
            $params[] = $from_date;
            $params[] = $to_date;
        }
        $sql .= "GROUP BY t.category_id;";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function getForTheLastXDays($owner_id, int $days) {
        $instance = Connection::getInstance();
        $conn = $instance->getConn();
        $sql1 = "SELECT DATE_FORMAT(t.time_event, '%e.%m') AS date, tc.type AS category, ROUND(SUM(t.amount), 2) as sum FROM transactions AS t
                 JOIN transaction_categories AS tc ON tc.id = t.category_id
                 JOIN accounts AS a ON a.id = t.account_id
                 WHERE a.owner_id = ? AND t.time_event > NOW() - INTERVAL ? day AND t.time_event < NOW() AND tc.type IS NOT NULL 
                 GROUP BY date, tc.type
                 ORDER BY date";
        $transactions = $conn->prepare($sql1);
        $transactions->execute([$owner_id, $days-1]);
        return $transactions->fetchAll(\PDO::FETCH_OBJ);
    }
}