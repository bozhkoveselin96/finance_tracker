<?php


namespace controller;


use exceptions\BadRequestException;
use exceptions\ForbiddenException;
use model\accounts\AccountDAO;
use model\categories\CategoryDAO;
use model\transactions\Transaction;
use model\transactions\TransactionDAO;

class TransactionController {
    public function add() {
        $response = [];
        if (isset($_POST['add_transaction']) && isset($_POST['account_id']) &&
            isset($_POST['category_id']) && !empty($_POST['time_event'])) {
            $accountDAO = new AccountDAO();
            $categoryDAO = new CategoryDAO();
            $account = $accountDAO->getAccountById($_POST['account_id']);
            $category = $categoryDAO->getCategoryById($_POST['category_id'], $account->getOwnerId());

            if (!$account) {
                throw new BadRequestException("No such account.");
            } elseif (!$category) {
                throw new BadRequestException("No such category.");
            }

            $transaction = new Transaction($_POST['amount'], $account, $category, $_POST['note'], $_POST['time_event']);

            if (!Validator::validateAmount($transaction->getAmount())) {
                throw new BadRequestException("Amount must be between 0 and " . MAX_AMOUNT . " inclusive!");
            } elseif (!Validator::validateDate($transaction->getTimeEvent())) {
                throw new BadRequestException("Please select valid day!");
            } elseif (!Validator::validateName($transaction->getNote())) {
                throw new BadRequestException("Name must be have between " . MIN_LENGTH_NAME . " and ". MAX_LENGTH_NAME . " symbols inclusive!");
            } elseif ($account->getOwnerId() != $_SESSION['logged_user']) {
                throw new ForbiddenException("This account is not yours.");
            }

            $transactionDAO = new TransactionDAO();
            $id = $transactionDAO->create($transaction);
            $transaction->setId($id);
            return new ResponseBody('Transaction added successfully!', $transaction);
        }
        throw new BadRequestException("Bad request.");
    }

    public function showUserTransactions() {
        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            $category_id = isset($_GET["category_id"]) ? $_GET["category_id"] : null;
            $transactionDAO = new TransactionDAO();
            $transactions = $transactionDAO->getByUserAndCategory($_SESSION['logged_user'], $category_id);
            return new ResponseBody(null, $transactions);
        }
        throw new BadRequestException("Bad request.");
    }

    public function delete() {
        if ($_POST["delete"]) {
            $transaction_id = $_POST["transaction_id"];
            $transactionDAO = new TransactionDAO();
            $transaction = $transactionDAO->getTransactionById($transaction_id);

            if ($transaction->getAccount()->getOwnerId() == $_SESSION['logged_user']) {
                $transactionDAO->deleteTransaction($transaction);
            } else {
                throw new ForbiddenException("This transaction is not yours!");
            }
        }
        throw new BadRequestException("Bad request.");
    }
}