<?php

declare(strict_types=1);

$host = 'localhost';
$db = 'sistema_crud';
$user = 'root';
$pass = 'SQL@Dev2134';

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $e) {

    die('Erro na conexão com o banco de dados.');

}