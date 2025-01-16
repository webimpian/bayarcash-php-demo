<?php

require './../config-v2.php';
require_once 'helper.php';

class ReturnUrlModel
{
    private PDO $pdo;
    private bool $tableCreated = false;

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

    public function insert($transaction): bool
    {
        try {
            $sql = "INSERT INTO return_url_transactions (
                transaction_id, exchange_reference_number, exchange_transaction_id,
                order_number, currency, amount, payer_bank_name,
                status, status_description, checksum
            ) VALUES (
                :transaction_id, :exchange_reference_number, :exchange_transaction_id,
                :order_number, :currency, :amount, :payer_bank_name,
                :status, :status_description, :checksum
            )";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'transaction_id'            => $transaction['transaction_id'],
                'exchange_reference_number' => $transaction['exchange_reference_number'],
                'exchange_transaction_id'   => $transaction['exchange_transaction_id'],
                'order_number'              => $transaction['order_number'],
                'currency'                  => $transaction['currency'],
                'amount'                    => $transaction['amount'],
                'payer_bank_name'           => $transaction['payer_bank_name'],
                'status'                    => $this->get_payment_status_name($transaction['status']),
                'status_description'        => $transaction['status_description'],
                'checksum'                  => $transaction['checksum']
            ]);
        } catch (\PDOException $e) {
            log_results('Insert return URL transaction failed: ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function update($transaction): bool
    {
        try {
            $sql = "UPDATE return_url_transactions SET
                exchange_transaction_id = :exchange_transaction_id,
                order_number = :order_number,
                currency = :currency,
                amount = :amount,
                payer_bank_name = :payer_bank_name,
                status = :status,
                status_description = :status_description,
                checksum = :checksum
            WHERE transaction_id = :transaction_id";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'exchange_transaction_id'   => $transaction['exchange_transaction_id'],
                'order_number'              => $transaction['order_number'],
                'currency'                  => $transaction['currency'],
                'amount'                    => $transaction['amount'],
                'payer_bank_name'           => $transaction['payer_bank_name'],
                'status'                    => $this->get_payment_status_name($transaction['status']),
                'status_description'        => $transaction['status_description'],
                'checksum'                  => $transaction['checksum'],
                'transaction_id'            => $transaction['transaction_id']
            ]);
        } catch (\PDOException $e) {
            log_results('Update return URL transaction failed: ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function getByTransactionId($transactionId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM return_url_transactions WHERE transaction_id = :transaction_id");
            $stmt->execute(['transaction_id' => $transactionId]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            log_results('Get return URL transaction by ID failed: ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function destroy(): bool
    {
        try {
            $this->pdo->exec('DROP TABLE IF EXISTS return_url_transactions');
            log_results('Return URL transactions table dropped successfully');
            return true;
        } catch (\PDOException $e) {
            log_results('DROP TABLE RETURN_URL_TRANSACTIONS FAILED: ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    private function ensureTableExists(): void
    {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'return_url_transactions'");
            if ($stmt->rowCount() == 0) {
                $this->pdo->exec("CREATE TABLE return_url_transactions (
                    id INT NOT NULL AUTO_INCREMENT,
                    transaction_id VARCHAR(50) UNIQUE,
                    exchange_reference_number VARCHAR(50),
                    exchange_transaction_id VARCHAR(50),
                    order_number VARCHAR(50),
                    currency VARCHAR(10),
                    amount DECIMAL(10, 2),
                    payer_bank_name VARCHAR(100),
                    status VARCHAR(50),
                    status_description TEXT,
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

    public function getAllTransactions()
    {
        try {
            // Debug the table existence first
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'return_url_transactions'");
            log_results('Table exists check result: ' . $checkTable->rowCount());

            // Debug the query
            $sql = "SELECT * FROM return_url_transactions ORDER BY created_at DESC";

            // Execute and debug results
            $stmt = $this->pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $results;
        } catch (\PDOException $e) {
            log_results('Get all return URL transactions failed: ' . $e->getMessage());
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function wasTableCreated(): bool
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