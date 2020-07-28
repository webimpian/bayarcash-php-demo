<?php
require('config.php');

class TransactionModel{


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
        $charset       = "utf8mb4";

        $dsn = "mysql:host=$this->host;dbname=$this->dbname;charset=$charset";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }


        $this->pdo = $pdo;

    }

    public function getAll()
    {
       return $this->pdo
                    ->query('SELECT * FROM transactions')
                    ->fetchAll();



    }

    public function getNewTransactions()
    {
       $status_name_array = [
          get_payment_status_name(0),
          get_payment_status_name(1)
       ];

       $in = str_repeat('?,', count($status_name_array) - 1) . '?';
       $sql = "SELECT * FROM transactions WHERE transaction_status IN ($in) AND DATE(created_at)=curdate()";

       $statement = $this->pdo->prepare($sql);

       $statement->execute($status_name_array);

       return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getNewTransactionsOrderRefNo()
    {
       $status_name_array = [
          get_payment_status_name(0),
          get_payment_status_name(1)
       ];

       $in = str_repeat('?,', count($status_name_array) - 1) . '?';
       $sql = "SELECT order_ref_no FROM transactions WHERE transaction_status IN ($in) AND DATE(created_at)=curdate()";

       $statement = $this->pdo->prepare($sql);

       $statement->execute($status_name_array);

       return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getSuccessfulTransactions()
    {
       return $this->pdo
                    ->query('SELECT * FROM transactions WHERE transaction_status='.$this->get_payment_status_name(3))
                    ->fetchAll();
    }

    public function getUnsuccessfulTransactions()
    {
       return $this->pdo
                    ->query('SELECT * FROM transactions WHERE transaction_status'.$this->get_payment_status_name(2))
                    ->fetchAll();
    }

    public function init($transaction)
    {
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
            'buyer_ic_no' => $transaction['buyer_ic_no'] ?? null, 
            'order_no' => $transaction['order_no'] ?? null,
            'transaction_status' => $this->get_payment_status_name(0)
        ]);
    }

    public function insert($transaction)
    {
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
            'order_ref_no' => $transaction['order_ref_no'] ?? null, 
            'order_no' => $transaction['order_no'] ?? null, 
            'transaction_currency' => $transaction['transaction_currency'] ?? null, 
            'order_amount' => $transaction['order_amount'] ?? null, 
            'buyer_name' => $transaction['buyer_name'] ?? null, 
            'buyer_email' => $transaction['buyer_email'] ?? null,
            'buyer_bank_name' => $transaction['buyer_bank_name'] ?? null,
            'transaction_status' => $this->get_payment_status_name($transaction['transaction_status']) ?? $this->get_payment_status_name(0), 
            'transaction_status_description' => $transaction['transaction_status_description'] ?? null,
            'transaction_datetime' => $transaction['transaction_datetime'] ?? null, 
            'transaction_gateway_id' => $transaction['transaction_gateway_id'] ?? null
        ]);
    }

    public function update($transaction)
    {
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
            'order_no' => $transaction['order_no'] ?? null, 
            'transaction_currency' => $transaction['transaction_currency'] ?? null, 
            'order_amount' => $transaction['order_amount'] ?? null, 
            'buyer_name' => $transaction['buyer_name'] ?? null, 
            'buyer_email' => $transaction['buyer_email'] ?? null,
            'buyer_bank_name' => $transaction['buyer_bank_name'] ?? null,
            'transaction_status' => $this->get_payment_status_name($transaction['transaction_status']) ?? null, 
            'transaction_status_description' => $transaction['transaction_status_description'] ?? null,
            'transaction_datetime' => $transaction['transaction_datetime'] ?? null, 
            'transaction_gateway_id' => $transaction['transaction_gateway_id'] ?? null,
            'order_ref_no' => $transaction['order_ref_no'] ?? null 
        ]);
    }

    public function updateByOrderNo($transaction)
    {
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
            'order_ref_no' => $transaction['order_ref_no'] ?? null,
            'transaction_currency' => $transaction['transaction_currency'] ?? null, 
            'order_amount' => $transaction['order_amount'] ?? null, 
            'buyer_name' => $transaction['buyer_name'] ?? null, 
            'buyer_email' => $transaction['buyer_email'] ?? null,
            'buyer_bank_name' => $transaction['buyer_bank_name'] ?? null,
            'transaction_status_description' => $transaction['transaction_status_description'] ?? null,
            'transaction_datetime' => $transaction['transaction_datetime'] ?? null, 
            'transaction_gateway_id' => $transaction['transaction_gateway_id'] ?? null,
            'order_no' => $transaction['order_no'] ?? null,
        ]);
    }

    public function setup()
    {
        $statement = $this->pdo->prepare('SHOW TABLES LIKE \'transactions\'');

        $statement->execute();

        $tables = $statement->fetchAll();

        $isTableExist = in_array('transactions', $tables);

        if($isTableExist) {
            echo 'transaction table exist';
            return;
        }

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
    }

    public function destroy()
    {
        $statement = $this->pdo->prepare('DROP TABLE transactions');

        return $statement->execute();
    }

    function get_payment_status_name($payment_status_code)
    {
        $payment_status_name_list = [
            'New',
            'Pending',
            'Unsuccessful',
            'Successful',
            'Cancelled'
        ];

        $is_Id = array_key_exists($payment_status_code, $payment_status_name_list);

        if (!$is_Id) {
            return;
        }

        return $payment_status_name_list[$payment_status_code];
    }
}
