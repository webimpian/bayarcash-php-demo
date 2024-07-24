<?php

require 'config.php';
require_once 'helper.php';

class TransactionModel
{
    private $pdo;
    private $host;
    private $dbname;
    private $username;
    private $password;

    public function __construct($config)
    {
        $this->host = $config['bayarcash_db_host'];
        $this->dbname = $config['bayarcash_db_dbname'];
        $this->username = $config['bayarcash_db_username'];
        $this->password = $config['bayarcash_db_password'];
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$this->host;dbname=$this->dbname;charset=$charset";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (\PDOException $e) {
            log_results('Connection to database failed');
            log_results($e->getMessage());

            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }

        $this->pdo = $pdo;
    }

    public function getAll()
    {
        try {
            return $this->pdo
                ->query('SELECT * FROM transactions')
                ->fetchAll();
        } catch (\PDOException $e) {
            log_results('Get all transaction failed');
            log_results($e->getMessage());

            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function getNewTransactions()
    {
        $status_name_array = [
            get_payment_status_name(0),
            get_payment_status_name(1),
        ];

        $in = str_repeat('?,', count($status_name_array) - 1).'?';
        $sql = "SELECT * FROM transactions WHERE transaction_status IN ($in) AND DATE(created_at)=curdate()";

        try {
            $statement = $this->pdo->prepare($sql);

            $statement->execute($status_name_array);

            return $statement->fetchAll(PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            log_results('Get new transaction failed');
            log_results($e->getMessage());

            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function getNewTransactionsOrderRefNo()
    {
        $status_name_array = [
            get_payment_status_name(0),
            get_payment_status_name(1),
        ];

        $in = str_repeat('?,', count($status_name_array) - 1).'?';
        $sql = "SELECT order_ref_no FROM transactions WHERE transaction_status IN ($in) AND DATE(created_at)=curdate()";

        $status_name_array = [
            get_payment_status_name(0),
            get_payment_status_name(1),
        ];

        try {
            $statement = $this->pdo->prepare($sql);

            $statement->execute($status_name_array);
        } catch (\PDOException $e) {
            log_results('Get new transaction order number failed');
            log_results($e->getMessage());

            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getSuccessfulTransactions()
    {
        try {
            return $this->pdo
                ->query('SELECT * FROM transactions WHERE transaction_status='.$this->get_payment_status_name(3))
                ->fetchAll();
        } catch (\PDOException $e) {
            log_results('Get successful transaction failed');
            log_results($e->getMessage());

            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function getUnsuccessfulTransactions()
    {
        try {
            return $this->pdo
                ->query('SELECT * FROM transactions WHERE transaction_status'.$this->get_payment_status_name(2))
                ->fetchAll();
        } catch (\PDOException $e) {
            log_results('Get unsuccessful transaction failed');
            log_results($e->getMessage());

            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function init($transaction)
    {
        try {
            return $this->pdo->prepare(
                'INSERT INTO transactions(
                    buyer_ic_no, 
                    order_no,
                    transaction_status
            )values(
                :buyer_ic_no, 
                :order_no,   
                :transaction_status
            )'
            )->execute([
                'buyer_ic_no'        => isset($transaction['buyer_ic_no']) ? $transaction['buyer_ic_no'] : null,
                'order_no'           => isset($transaction['order_no']) ? $transaction['order_no'] : null,
                'transaction_status' => $this->get_payment_status_name(0),
            ]);
        } catch (\PDOException $e) {
            log_results('Init transaction failed');
            log_results($e->getMessage());

            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function insert($transaction)
    {
        try {
            return $this->pdo->prepare(
                'INSERT INTO transactions(
                order_ref_no, order_no, transaction_currency, order_amount, buyer_name, buyer_email,
                buyer_bank_name, transaction_status, transaction_status_description,
                transaction_datetime, transaction_gateway_id
            )values(
                :order_ref_no, :order_no, :transaction_currency, :order_amount, :buyer_name, :buyer_email,
                :buyer_bank_name, :transaction_status, :transaction_status_description,
                :transaction_datetime, :transaction_gateway_id
            )'
            )->execute([
                'order_ref_no'                   => isset($transaction['order_ref_no']) ? $transaction['order_ref_no'] : null,
                'order_no'                       => isset($transaction['order_no']) ? $transaction['order_no'] : null,
                'transaction_currency'           => isset($transaction['transaction_currency']) ? $transaction['transaction_currency'] : null,
                'order_amount'                   => isset($transaction['order_amount']) ? $transaction['order_amount'] : null,
                'buyer_name'                     => isset($transaction['buyer_name']) ? $transaction['buyer_name'] : null,
                'buyer_email'                    => isset($transaction['buyer_email']) ? $transaction['buyer_email'] : null,
                'buyer_bank_name'                => isset($transaction['buyer_bank_name']) ? $transaction['buyer_bank_name'] : null,
                'transaction_status'             => isset($transaction['transaction_status']) ? $this->get_payment_status_name($transaction['transaction_status']) : $this->get_payment_status_name(0),
                'transaction_status_description' => isset($transaction['transaction_status_description']) ? $transaction['transaction_status_description'] : null,
                'transaction_datetime'           => isset($transaction['transaction_datetime']) ? $transaction['transaction_datetime'] : null,
                'transaction_gateway_id'         => isset($transaction['transaction_gateway_id']) ? $transaction['transaction_gateway_id'] : null,
            ]);
        } catch (\PDOException $e) {
            log_results('Insert transaction failed');
            log_results($e->getMessage());

            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function update($transaction)
    {
        try {
            return $this->pdo->prepare('
            UPDATE transactions SET
            order_no = :order_no,
            transaction_currency = :transaction_currency,
            order_amount = :order_amount,
            buyer_name = :buyer_name,
            buyer_email = :buyer_email,
            buyer_bank_name = :buyer_bank_name,
            transaction_status = :transaction_status,
            transaction_status_description = :transaction_status_description,
            transaction_datetime =  :transaction_datetime,
            transaction_gateway_id = :transaction_gateway_id
            where
            order_ref_no = :order_ref_no
        ')->execute([
                'order_no'                       => isset($transaction['order_no']) ? $transaction['order_no'] : null,
                'transaction_currency'           => isset($transaction['transaction_currency']) ? $transaction['transaction_currency'] : null,
                'order_amount'                   => isset($transaction['order_amount']) ? $transaction['order_amount'] : null,
                'buyer_name'                     => isset($transaction['buyer_name']) ? $transaction['buyer_name'] : null,
                'buyer_email'                    => isset($transaction['buyer_email']) ? $transaction['buyer_email'] : null,
                'buyer_bank_name'                => isset($transaction['buyer_bank_name']) ? $transaction['buyer_bank_name'] : null,
                'transaction_status'             => isset($transaction['transaction_status']) ? $this->get_payment_status_name($transaction['transaction_status']) : $this->get_payment_status_name(0),
                'transaction_status_description' => isset($transaction['transaction_status_description']) ? $transaction['transaction_status_description'] : null,
                'transaction_datetime'           => isset($transaction['transaction_datetime']) ? $transaction['transaction_datetime'] : null,
                'transaction_gateway_id'         => isset($transaction['transaction_gateway_id']) ? $transaction['transaction_gateway_id'] : null,
                'order_ref_no'                   => isset($transaction['order_ref_no']) ? $transaction['order_ref_no'] : null,
            ]);
        } catch (\PDOException $e) {
            log_results('Update transaction failed');
            log_results($e->getMessage());

            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function updateByOrderNo($transaction)
    {
        try {
            return $this->pdo->prepare('
            UPDATE transactions SET
            order_ref_no = :order_ref_no,
            transaction_currency = :transaction_currency,
            order_amount = :order_amount,
            buyer_name = :buyer_name,
            buyer_email = :buyer_email,
            buyer_bank_name = :buyer_bank_name,
            transaction_status_description = :transaction_status_description,
            transaction_datetime =  :transaction_datetime,
            transaction_gateway_id = :transaction_gateway_id
            where
            order_no = :order_no
        ')->execute([
                'order_ref_no'                   => isset($transaction['order_ref_no']) ? $transaction['order_ref_no'] : null,
                'transaction_currency'           => isset($transaction['transaction_currency']) ? $transaction['transaction_currency'] : null,
                'order_amount'                   => isset($transaction['order_amount']) ? $transaction['order_amount'] : null,
                'buyer_name'                     => isset($transaction['buyer_name']) ? $transaction['buyer_name'] : null,
                'buyer_email'                    => isset($transaction['buyer_email']) ? $transaction['buyer_email'] : null,
                'buyer_bank_name'                => isset($transaction['buyer_bank_name']) ? $transaction['buyer_bank_name'] : null,
                'transaction_status_description' => isset($transaction['transaction_status_description']) ? $transaction['transaction_status_description'] : null,
                'transaction_datetime'           => isset($transaction['transaction_datetime']) ? $transaction['transaction_datetime'] : null,
                'transaction_gateway_id'         => isset($transaction['transaction_gateway_id']) ? $transaction['transaction_gateway_id'] : null,
                'order_no'                       => isset($transaction['order_no']) ? $transaction['order_no'] : null,
            ]);
        } catch (\PDOException $e) {
            log_results('Update transaction by order number failed');
            log_results($e->getMessage());

            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function setup()
    {
        $statement = $this->pdo->prepare('SHOW TABLES LIKE \'transactions\'');

        $statement->execute();

        $tables = $statement->fetchAll();


        if (count($tables)) {
            $isTableExist = in_array('transactions', array_values($tables[0]));

            if ($isTableExist) {
                echo 'Transaction table exist';

                return;
            }
        }

        try {
            $this->pdo->query('
        CREATE TABLE transactions (
         id int NOT NULL AUTO_INCREMENT,
         buyer_ic_no VARCHAR(50),
         order_no VARCHAR(50),
         transaction_currency VARCHAR(50), 
         order_amount VARCHAR(50), 
         buyer_name VARCHAR(50),
         buyer_email VARCHAR(50),
         buyer_bank_name VARCHAR(50),
         transaction_status VARCHAR(50),
         transaction_status_description VARCHAR(255),
         transaction_datetime VARCHAR(50),
         transaction_gateway_id VARCHAR(50),
         order_ref_no VARCHAR(50),
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
         PRIMARY KEY (id)
        ) 
        ');
        } catch (\PDOException $e) {
            log_results('Setup transaction table failed');
            log_results($e->getMessage());

            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function destroy()
    {
        try {
            $statement = $this->pdo->prepare('DROP TABLE transactions');

            return $statement->execute();
        } catch (\PDOException $e) {
            log_results('DROP TABLE TRANSACTION FAILED');
            log_results($e->getMessage());

            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
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

        $is_Id = array_key_exists($payment_status_code, $payment_status_name_list);

        if (!$is_Id) {
            return;
        }

        return $payment_status_name_list[$payment_status_code];
    }
}
