<?php
/**
 * Database base class: connection only. Every Model class extends this.
 */
require_once __DIR__ . '/db_cred.php';

class Database
{
    protected mysqli $conn;
    private static ?mysqli $shared = null;   // one connection per request

    public function __construct()
    {
        if (self::$shared === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            try {
                $conn = mysqli_init();
                $flags = 0;
                if (DB_SSL) {
                    $conn->ssl_set(null, null, null, null, null);
                    $flags = MYSQLI_CLIENT_SSL;
                }
                $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, null, $flags);
                $conn->set_charset('utf8mb4');
                self::$shared = $conn;
            } catch (mysqli_sql_exception $e) {
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(500);
                die('We could not connect to the database. Please try again shortly.');
            }
        }
        $this->conn = self::$shared;
    }

    /** Run a prepared SELECT and return all rows (empty array if none). */
    protected function fetchAll(string $sql, string $types = '', array $params = []): array
    {
        $stmt = $this->conn->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /** Run a prepared SELECT and return one row or null. */
    protected function fetchOne(string $sql, string $types = '', array $params = []): ?array
    {
        $rows = $this->fetchAll($sql, $types, $params);
        return $rows[0] ?? null;
    }

    /** Run a prepared INSERT/UPDATE/DELETE and return the number of affected rows. */
    protected function execute(string $sql, string $types = '', array $params = []): int
    {
        $stmt = $this->conn->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $n = $stmt->affected_rows;
        $stmt->close();
        return $n;
    }
}
