<?php
// orders/OrderModel.php
// All PDO queries for the orders module.

declare(strict_types=1);

class OrderModel
{
    public function __construct(private PDO $pdo) {}

    // ── Read ─────────────────────────────────────────────────

    /** Single order – caller passes their user ID + role for ownership check. */
    public function getOne(int $orderId, int $userId, string $role): array|false
    {
        $col  = $role === 'client' ? 'o.client_id' : 'o.freelancer_id';
        $stmt = $this->pdo->prepare(
            "SELECT o.*,
                    s.image_path        AS service_image,
                    s.description       AS service_description,
                    u_c.full_name       AS client_name,
                    u_c.email           AS client_email,
                    u_f.full_name       AS freelancer_name,
                    u_f.email           AS freelancer_email
             FROM   orders o
             JOIN   services s  ON s.id = o.service_id
             JOIN   users u_c   ON u_c.id = o.client_id
             JOIN   users u_f   ON u_f.id = o.freelancer_id
             WHERE  o.id = :oid AND {$col} = :uid
             LIMIT  1"
        );
        $stmt->execute([':oid' => $orderId, ':uid' => $userId]);
        return $stmt->fetch();
    }

    /** All orders for a client, newest first. */
    public function getByClient(int $clientId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.*,
                    u_f.full_name AS freelancer_name,
                    s.image_path  AS service_image
             FROM   orders o
             JOIN   users u_f ON u_f.id = o.freelancer_id
             JOIN   services s ON s.id  = o.service_id
             WHERE  o.client_id = :cid
             ORDER  BY o.created_at DESC"
        );
        $stmt->execute([':cid' => $clientId]);
        return $stmt->fetchAll();
    }

    /** All orders for a freelancer, newest first. */
    public function getByFreelancer(int $freelancerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.*,
                    u_c.full_name AS client_name,
                    s.image_path  AS service_image
             FROM   orders o
             JOIN   users u_c  ON u_c.id = o.client_id
             JOIN   services s ON s.id   = o.service_id
             WHERE  o.freelancer_id = :fid
             ORDER  BY o.created_at DESC"
        );
        $stmt->execute([':fid' => $freelancerId]);
        return $stmt->fetchAll();
    }

    /** Count orders by status for a user. */
    public function countByStatus(int $userId, string $role): array
    {
        $col  = $role === 'client' ? 'client_id' : 'freelancer_id';
        $stmt = $this->pdo->prepare(
            "SELECT status, COUNT(*) AS cnt
             FROM   orders
             WHERE  {$col} = :uid
             GROUP  BY status"
        );
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll();

        $counts = ['pending' => 0, 'accepted' => 0, 'in_progress' => 0,
                   'completed' => 0, 'cancelled' => 0];
        foreach ($rows as $r) {
            $counts[$r['status']] = (int) $r['cnt'];
        }
        return $counts;
    }

    // ── Write ────────────────────────────────────────────────

    /** Place a new order. Returns new order ID. */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO orders
                (service_id, client_id, freelancer_id,
                 service_title, amount, delivery_days, requirements, status)
             VALUES
                (:service_id, :client_id, :freelancer_id,
                 :service_title, :amount, :delivery_days, :requirements, 'pending')"
        );
        $stmt->execute([
            ':service_id'    => $data['service_id'],
            ':client_id'     => $data['client_id'],
            ':freelancer_id' => $data['freelancer_id'],
            ':service_title' => $data['service_title'],
            ':amount'        => $data['amount'],
            ':delivery_days' => $data['delivery_days'],
            ':requirements'  => $data['requirements'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Freelancer accepts a pending order. */
    public function accept(int $orderId, int $freelancerId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE orders
             SET    status = 'accepted', accepted_at = NOW()
             WHERE  id = :oid
               AND  freelancer_id = :fid
               AND  status = 'pending'"
        );
        $stmt->execute([':oid' => $orderId, ':fid' => $freelancerId]);
        return $stmt->rowCount() > 0;
    }

    /** Freelancer marks an order as completed. */
    public function complete(int $orderId, int $freelancerId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE orders
             SET    status = 'completed', completed_at = NOW()
             WHERE  id = :oid
               AND  freelancer_id = :fid
               AND  status IN ('accepted', 'in_progress')"
        );
        $stmt->execute([':oid' => $orderId, ':fid' => $freelancerId]);
        return $stmt->rowCount() > 0;
    }

    /** Either party cancels — only allowed on pending/accepted. */
    public function cancel(int $orderId, int $userId, string $role): bool
    {
        $col  = $role === 'client' ? 'client_id' : 'freelancer_id';
        $stmt = $this->pdo->prepare(
            "UPDATE orders
             SET    status = 'cancelled', cancelled_at = NOW()
             WHERE  id = :oid
               AND  {$col} = :uid
               AND  status IN ('pending', 'accepted')"
        );
        $stmt->execute([':oid' => $orderId, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /** Check if client already has a non-cancelled order for this service. */
    public function hasActiveOrder(int $clientId, int $serviceId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM orders
             WHERE  client_id = :cid
               AND  service_id = :sid
               AND  status NOT IN ('cancelled', 'completed')
             LIMIT  1"
        );
        $stmt->execute([':cid' => $clientId, ':sid' => $serviceId]);
        return (bool) $stmt->fetch();
    }
}
