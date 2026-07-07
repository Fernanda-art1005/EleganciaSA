<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $conexao = null;
    private static string $db_type = 'mysql';

    public static function getConnection(): PDO {
        if (self::$conexao === null) {
            // Configurações padrão para o ambiente MySQL do XAMPP
            $host = "127.0.0.1";
            $dbname = "elegancia_premium";
            $user = "root";
            $pass = "";

            try {
                // Tenta conectar no MySQL (XAMPP local)
                self::$conexao = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_TIMEOUT => 2
                    ]
                );
                self::$db_type = 'mysql';
            } catch (PDOException $e) {
                throw new PDOException("Erro ao conectar com o banco de dados MySQL: " . $e->getMessage());
            }
        }
        return self::$conexao;
    }

    public static function getDbType(): string {
        return self::$db_type;
    }
}
