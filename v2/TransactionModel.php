<?php

require './../config-v2.php';
require_once 'helper.php';

class TransactionModel
{
    private $pdo;
    private $tableCreated = false;

    public function __construct($config)
    {
        $dsn = "mysql:host={$config['bayarcash_db_host']};dbname={$config['bayarcash_db_dbname']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $config['bayarcash_db_username'], $config['bayarcash_db_password'], $options);
        } catch (\PDOException $e) {
            log_results('Connection to database failed: ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }

        $this->ensureTableExists();
    }

    public function insert($transaction)
    {
        try {
            $sql = "INSERT INTO transactions (
                record_type, transaction_id, exchange_reference_number, exchange_transaction_id,
                order_number, currency, amount, payer_name, payer_email, payer_bank_name,
                status, status_description, datetime, checksum
            ) VALUES (
                :record_type, :transaction_id, :exchange_reference_number, :exchange_transaction_id,
                :order_number, :currency, :amount, :payer_name, :payer_email, :payer_bank_name,
                :status, :status_description, :datetime, :checksum
            )";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'record_type'               => $transaction['record_type'],
                'transaction_id'            => $transaction['transaction_id'],
                'exchange_reference_number' => $transaction['exchange_reference_number'],
                'exchange_transaction_id'   => $transaction['exchange_transaction_id'],
                'order_number'              => $transaction['order_number'],
                'currency'                  => $transaction['currency'],
                'amount'                    => $transaction['amount'],
                'payer_name'                => $transaction['payer_name'],
                'payer_email'               => $transaction['payer_email'],
                'payer_bank_name'           => $transaction['payer_bank_name'],
                'status'                    => $this->get_payment_status_name($transaction['status']),
                'status_description'        => $transaction['status_description'],
                'datetime'                  => $transaction['datetime'],
                'checksum'                  => $transaction['checksum']
            ]);
        } catch (\PDOException $e) {
            log_results('Insert transaction failed: ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function update($transaction)
    {
        try {
            $sql = "UPDATE transactions SET
                record_type = :record_type,
                exchange_transaction_id = :exchange_transaction_id,
                order_number = :order_number,
                currency = :currency,
                amount = :amount,
                payer_name = :payer_name,
                payer_email = :payer_email,
                payer_bank_name = :payer_bank_name,
                status = :status,
                status_description = :status_description,
                datetime = :datetime,
                checksum = :checksum
            WHERE transaction_id = :transaction_id";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'record_type'               => $transaction['record_type'],
                'exchange_transaction_id'   => $transaction['exchange_transaction_id'],
                'order_number'              => $transaction['order_number'],
                'currency'                  => $transaction['currency'],
                'amount'                    => $transaction['amount'],
                'payer_name'                => $transaction['payer_name'],
                'payer_email'               => $transaction['payer_email'],
                'payer_bank_name'           => $transaction['payer_bank_name'],
                'status'                    => $this->get_payment_status_name($transaction['status']),
                'status_description'        => $transaction['status_description'],
                'datetime'                  => $transaction['datetime'],
                'checksum'                  => $transaction['checksum'],
                'transaction_id'            => $transaction['transaction_id']
            ]);
        } catch (\PDOException $e) {
            log_results('Update transaction failed: ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function getByTransactionId($transactionId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE transaction_id = :transaction_id");
            $stmt->execute(['transaction_id' => $transactionId]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            log_results('Get transaction by ID failed: ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function getNewTransactions()
    {
        try {
            $newStatus = $this->get_payment_status_name(0);
            $pendingStatus = $this->get_payment_status_name(1);
            $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE status IN (:new_status, :pending_status) AND DATE(created_at) = CURDATE()");
            $stmt->execute(['new_status' => $newStatus, 'pending_status' => $pendingStatus]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            log_results('Get new transactions failed: ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function destroy()
    {
        try {
            $this->pdo->exec('DROP TABLE IF EXISTS transactions');
            log_results('Transactions table dropped successfully');
            return true;
        } catch (\PDOException $e) {
            log_results('DROP TABLE TRANSACTION FAILED: ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    private function ensureTableExists()
    {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'transactions'");
            if ($stmt->rowCount() == 0) {
                $this->pdo->exec("CREATE TABLE transactions (
                    id INT NOT NULL AUTO_INCREMENT,
                    record_type VARCHAR(50),
                    transaction_id VARCHAR(50) UNIQUE,
                    exchange_reference_number VARCHAR(50),
                    exchange_transaction_id VARCHAR(50),
                    order_number VARCHAR(50),
                    currency VARCHAR(10),
                    amount DECIMAL(10, 2),
                    payer_name VARCHAR(100),
                    payer_email VARCHAR(100),
                    payer_bank_name VARCHAR(100),
                    status VARCHAR(50),
                    status_description TEXT,
                    datetime DATETIME,
                    checksum VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                )");
                $this->tableCreated = true;
            }
        } catch (\PDOException $e) {
            log_results('Ensure table exists failed: ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }


    public function getAllTransactions(): bool|array
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM transactions ORDER BY created_at DESC");
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            log_results('Get all transactions failed: ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function wasTableCreated()
    {
        return $this->tableCreated;
    }

    public function get_payment_status_name($payment_status_code)
    {
        $payment_status_name_list = [
            'New',
            'Pending',
            'Unsuccessful',
            'Successful',
            'Cancelled',
        ];

        if (is_numeric($payment_status_code) && isset($payment_status_name_list[$payment_status_code])) {
            return $payment_status_name_list[$payment_status_code];
        } elseif (in_array($payment_status_code, $payment_status_name_list)) {
            return $payment_status_code;
        } else {
            return 'Unknown';
        }
    }
}