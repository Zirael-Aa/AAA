<?php
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host   = 'MySQL-8.4';
        $dbname = 'kurs';
        $user   = 'root';
        $pass   = '';   

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Ошибка подключения к БД: ' . $e->getMessage());
        }
    }
    return $pdo;
}
?>