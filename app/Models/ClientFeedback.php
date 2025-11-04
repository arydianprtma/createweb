<?php
declare(strict_types=1);
namespace App\Models;

class ClientFeedback
{
    private \mysqli|null $db = null;

    public function __construct()
    {
        // Gunakan konfigurasi global seperti model lain
        require_once _DIR_ . '/../config.php';
        \mysqli_report(MYSQLI_REPORT_OFF);
        $this->db = @\mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$this->db) {
            // Jangan hentikan aplikasi; biarkan null untuk fallback
            $this->db = null;
        }
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        if ($this->db === null) { return; }
        $sql = "CREATE TABLE IF NOT EXISTS client_feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            client_name VARCHAR(100) NULL,
            organization VARCHAR(150) NULL,
            rating TINYINT NULL,
            comment TEXT NULL,
            status ENUM('pending','submitted') NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        @\mysqli_query($this->db, $sql);

        // Tambahkan kolom organization jika belum ada
        $colRes = @\mysqli_query($this->db, "SHOW COLUMNS FROM client_feedback LIKE 'organization'");
        if ($colRes && $colRes->num_rows === 0) {
            @\mysqli_query($this->db, "ALTER TABLE client_feedback ADD COLUMN organization VARCHAR(150) NULL AFTER client_name");
        }
    }

    public function generateToken(int $length = 24): string
    {
        $bytes = random_bytes($length);
        return substr(bin2hex($bytes), 0, $length);
    }

    public function createShareLink(?int $orderId = null): array
    {
        if ($this->db === null) { return ['id' => 0, 'token' => '']; }
        $token = $this->generateToken(24);
        $stmt = $this->db->prepare("INSERT INTO client_feedback (order_id, token, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param('is', $orderId, $token);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return ['id' => $id, 'token' => $token];
    }

    public function getByToken(string $token): ?array
    {
        if ($this->db === null) { return null; }
        $stmt = $this->db->prepare("SELECT id, order_id, token, client_name, organization, rating, comment, status, created_at FROM client_feedback WHERE token = ? LIMIT 1");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        // Coba gunakan get_result jika tersedia (mysqlnd). Jika tidak, fallback ke bind_result.
        $row = null;
        $result = method_exists($stmt, 'get_result') ? $stmt->get_result() : false;
        if ($result instanceof \mysqli_result) {
            $row = $result->fetch_assoc() ?: null;
        } else {
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $orderId, $tok, $clientName, $organization, $rating, $comment, $status, $createdAt);
                if ($stmt->fetch()) {
                    $row = [
                        'id' => (int)$id,
                        'order_id' => $orderId !== null ? (int)$orderId : null,
                        'token' => (string)$tok,
                        'client_name' => $clientName !== null ? (string)$clientName : null,
                        'organization' => $organization !== null ? (string)$organization : null,
                        'rating' => $rating !== null ? (int)$rating : null,
                        'comment' => $comment !== null ? (string)$comment : null,
                        'status' => (string)$status,
                        'created_at' => (string)$createdAt,
                    ];
                }
            }
        }
        $stmt->close();
        if ($row !== null) { return $row; }

        // Fallback terakhir: query langsung jika prepared gagal (untuk kompatibilitas lingkungan tertentu)
        $safeToken = $this->db->real_escape_string($token);
        $sql = "SELECT id, order_id, token, client_name, organization, rating, comment, status, created_at FROM client_feedback WHERE token = '" . $safeToken . "' LIMIT 1";
        $res = $this->db->query($sql);
        if ($res instanceof \mysqli_result) {
            $directRow = $res->fetch_assoc();
            return $directRow ?: null;
        }
        return null;
    }

    public function submitFeedback(string $token, ?string $clientName, ?string $organization, int $rating, ?string $comment): bool
    {
        if ($this->db === null) { return false; }
        if ($rating < 1 || $rating > 5) return false;
        $stmt = $this->db->prepare("UPDATE client_feedback SET client_name = ?, organization = ?, rating = ?, comment = ?, status = 'submitted' WHERE token = ?");
        $stmt->bind_param('ssiss', $clientName, $organization, $rating, $comment, $token);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function all(): array
    {
        if ($this->db === null) { return []; }
        $res = $this->db->query("SELECT * FROM client_feedback ORDER BY created_at DESC");
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Ambil feedback terbaru yang sudah disubmit, dibatasi oleh $limit.
     */
    public function getRecentSubmitted(int $limit = 6): array
    {
        if ($this->db === null) { return []; }
        $limit = max(1, min($limit, 20));

        $sql = "SELECT client_name, organization, rating, comment, created_at FROM client_feedback \n                WHERE status = 'submitted' AND rating IS NOT NULL \n                ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) { return []; }
        $stmt->bind_param('i', $limit);
        $stmt->execute();

        $rows = [];
        $result = method_exists($stmt, 'get_result') ? $stmt->get_result() : false;
        if ($result instanceof \mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        } else {
            $stmt->store_result();
            $stmt->bind_result($clientName, $organization, $rating, $comment, $createdAt);
            while ($stmt->fetch()) {
                $rows[] = [
                    'client_name' => $clientName !== null ? (string)$clientName : null,
                    'organization' => $organization !== null ? (string)$organization : null,
                    'rating' => $rating !== null ? (int)$rating : null,
                    'comment' => $comment !== null ? (string)$comment : null,
                    'created_at' => (string)$createdAt,
                ];
            }
        }
        $stmt->close();
        return $rows;
    }

    /**
     * Statistik sederhana: rata-rata rating dan jumlah ulasan yang disubmit.
     */
    public function getStats(): array
    {
        if ($this->db === null) { return ['avg_rating' => null, 'count' => 0, 'satisfied' => 0]; }
        $avg = null; $count = 0; $satisfied = 0;

        // AVG rating
        $sqlAvg = "SELECT AVG(rating) AS avg_rating FROM client_feedback WHERE status='submitted' AND rating IS NOT NULL";
        $resAvg = $this->db->query($sqlAvg);
        if ($resAvg instanceof \mysqli_result) {
            $row = $resAvg->fetch_assoc();
            if ($row && isset($row['avg_rating'])) {
                $avg = $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 1) : null;
            }
        }

        // COUNT submitted
        $sqlCnt = "SELECT COUNT(*) AS cnt FROM client_feedback WHERE status='submitted' AND rating IS NOT NULL";
        $resCnt = $this->db->query($sqlCnt);
        if ($resCnt instanceof \mysqli_result) {
            $row = $resCnt->fetch_assoc();
            if ($row && isset($row['cnt'])) {
                $count = (int)$row['cnt'];
            }
        }

        // COUNT satisfied (rating >= 4)
        $sqlSat = "SELECT COUNT(*) AS cnt FROM client_feedback WHERE status='submitted' AND rating IS NOT NULL AND rating >= 4";
        $resSat = $this->db->query($sqlSat);
        if ($resSat instanceof \mysqli_result) {
            $row = $resSat->fetch_assoc();
            if ($row && isset($row['cnt'])) {
                $satisfied = (int)$row['cnt'];
            }
        }

        return ['avg_rating' => $avg, 'count' => $count, 'satisfied' => $satisfied];
    }
}