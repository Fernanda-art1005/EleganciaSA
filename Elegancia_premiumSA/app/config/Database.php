<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $conexao = null;

    public static function getConnection(): PDO {
        if (self::$conexao === null) {
            try {
                $host = "localhost";
                $dbname = "sgf_db";
                $user = "root";
                $pass = "root";

                self::$conexao = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_PERSISTENT => true
                    ]
                );
            } catch (PDOException $e) {
                error_log("Erro de Conexão SGF: " . $e->getMessage());
                die("Erro interno de comunicação com o banco de dados.");
            }
        }
        return self::$conexao;
    }
}
