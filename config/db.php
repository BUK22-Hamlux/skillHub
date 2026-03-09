<?php
// config/db.php – PDO database connection

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'skillhub');
define('DB_USER', 'root');         // change to your DB user
define('DB_PASS', '');             // change to your DB password
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never expose raw PDO errors to the browser in production
            error_log('[SkillHub DB] ' . $e->getMessage());
            http_response_code(500);
            exit('Database connection failed. Please try again later.');
        }
    }

    return $pdo;
}
