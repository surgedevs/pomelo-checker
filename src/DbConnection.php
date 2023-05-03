<?php

class DbConnection
{
    private static self $instance;
    private PDO $connection;

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        $config = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.json'), true);
        $dsn = 'mysql:host=' . $config['database']['host'] . ';dbname=' . $config['database']['dbname'] . ';port=3306;charset=utf8mb4';

        $this->connection = new PDO($dsn, $config['database']['username'], $config['database']['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}