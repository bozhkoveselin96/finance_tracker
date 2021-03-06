<?php


namespace model\planned_payments;


use model\accounts\AccountDAO;
use model\categories\CategoryDAO;
use model\Connection;
use model\transactions\Transaction;
use model\users\User;

class PlannedPaymentDAO {
    public function create(PlannedPayment $plannedPayment) {
        $conn = Connection::getInstance()->getConn();
        $parameters = [];
        $parameters[] = $plannedPayment->getDayForPayment();
        $parameters[] = $plannedPayment->getAmount();
        $parameters[] = $plannedPayment->getCurrency();
        $parameters[] = $plannedPayment->getAccount()->getId();
        $parameters[] = $plannedPayment->getCategory()->getId();

        $sql = "INSERT INTO planned_payments(day_for_payment, amount, currency, account_id, category_id, status, date_created) 
                VALUES (?, ?, ?, ?, ?, 1, CURRENT_DATE)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($parameters);
        $plannedPayment->setId($conn->lastInsertId());
    }

    public function getAll($user_id) {
        $conn = Connection::getInstance()->getConn();
        $sql = "SELECT pp.id, pp.day_for_payment, pp.amount, pp.currency, pp.account_id, pp.category_id, pp.status 
                FROM planned_payments AS pp 
                JOIN accounts AS a ON pp.account_id = a.id  WHERE a.owner_id = ? 
                ORDER BY pp.date_created DESC;";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        $plannedPayments = [];
        $categoryDAO = new CategoryDAO();
        $accountDAO = new AccountDAO();
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $value) {
            $plannedPayment = new PlannedPayment($value->day_for_payment,
                                                 $value->amount,
                                                 $value->currency,
                                                 $accountDAO->getAccountById($value->account_id),
                                                 $categoryDAO->getCategoryById($value->category_id,$_SESSION['logged_user']));
            $plannedPayment->setStatus($value->status);
            $plannedPayment->setId($value->id);
            $plannedPayments[] = $plannedPayment;
        }
        return $plannedPayments;
    }

    public function getPlannedPaymentById($planned_payment_id) {
        $conn = Connection::getInstance()->getConn();
        $sql = "SELECT pp.id, pp.day_for_payment, pp.amount, pp.currency, pp.account_id, pp.category_id, pp.status 
                FROM planned_payments AS pp 
                WHERE pp.id = ?;";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$planned_payment_id]);

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(\PDO::FETCH_OBJ);
            $categoryDAO = new CategoryDAO();
            $accountDAO = new AccountDAO();
            $plannedPayment = new PlannedPayment($row->day_for_payment,
                                                 $row->amount,
                                                 $row->currency,
                                                 $accountDAO->getAccountById($row->account_id),
                                                 $categoryDAO->getCategoryById($row->category_id, $_SESSION['logged_user']));
            $plannedPayment->setId($row->id);
            $plannedPayment->setStatus($row->status);
            return $plannedPayment;
        }
        return false;
    }

    public function deletePlannedPayment(PlannedPayment $plannedPayment) {
        $conn = Connection::getInstance()->getConn();
        $sql = "DELETE FROM planned_payments WHERE id = ?;";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$plannedPayment->getId()]);
    }

    public function editPlannedPayment(PlannedPayment $planned_payment) {
        $conn = Connection::getInstance()->getConn();
        $sql = "UPDATE planned_payments SET day_for_payment = ?, amount = ?, status = ?
                WHERE id = ?;";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$planned_payment->getDayForPayment(),
                        $planned_payment->getAmount(),
                        $planned_payment->getStatus(),
                        $planned_payment->getId()]);
    }

    public function getPlannedPaymentsForToday($after_this_day = false) {
        $conn = Connection::getInstance()->getConn();
        $sql = "SELECT pp.id, pp.day_for_payment, pp.amount, pp.currency, pp.account_id, pp.category_id, pp.status 
                FROM planned_payments AS pp 
                WHERE pp.status = 1 ";
        if (!$after_this_day) {
            $sql .= 'AND pp.day_for_payment = DAYOFMONTH(NOW());';
        } else {
            $sql .= 'AND pp.day_for_payment >= DAYOFMONTH(NOW());';
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute([]);
        $plannedPayments = [];

        $categoryDAO = new CategoryDAO();
        $accountDAO = new AccountDAO();

        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $value) {
            $account = $accountDAO->getAccountById($value->account_id);
            $plannedPayment = new PlannedPayment($value->day_for_payment,
                $value->amount,
                $value->currency,
                $account,
                $categoryDAO->getCategoryById($value->category_id, $account->getOwnerId()));
            $plannedPayment->setStatus($value->status);
            $plannedPayment->setId($value->id);
            $plannedPayments[] = $plannedPayment;
        }
        return $plannedPayments;
    }
}