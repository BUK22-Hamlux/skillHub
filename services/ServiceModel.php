<?php
// services/ServiceModel.php
// All PDO queries for the services module — no raw SQL in controllers.

declare(strict_types=1);

class ServiceModel
{
    public function __construct(private PDO $pdo) {}

    // ── Read ─────────────────────────────────────────────────

    /** All active + paused services owned by a freelancer. */
    public function getByFreelancer(int $freelancerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*, sc.name AS category_name
             FROM   services s
             LEFT JOIN service_categories sc ON sc.id = s.category_id
             WHERE  s.freelancer_id = :fid
               AND  s.status != 'deleted'
             ORDER BY s.created_at DESC"
        );
        $stmt->execute([':fid' => $freelancerId]);
        return $stmt->fetchAll();
    }

    /** Single service — verifies ownership before returning. */
    public function getOne(int $id, int $freelancerId): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*, sc.name AS category_name
             FROM   services s
             LEFT JOIN service_categories sc ON sc.id = s.category_id
             WHERE  s.id = :id
               AND  s.freelancer_id = :fid
               AND  s.status != 'deleted'
             LIMIT 1"
        );
        $stmt->execute([':id' => $id, ':fid' => $freelancerId]);
        return $stmt->fetch();
    }

    /** All categories for <select> dropdown. */
    public function getCategories(): array
    {
        return $this->pdo
            ->query('SELECT id, name FROM service_categories ORDER BY name')
            ->fetchAll();
    }

    // ── Write ────────────────────────────────────────────────

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO services
                (freelancer_id, category_id, title, description,
                 price, delivery_days, image_path, status)
             VALUES
                (:freelancer_id, :category_id, :title, :description,
                 :price, :delivery_days, :image_path, 'active')"
        );
        $stmt->execute([
            ':freelancer_id' => $data['freelancer_id'],
            ':category_id'   => $data['category_id']   ?: null,
            ':title'         => $data['title'],
            ':description'   => $data['description'],
            ':price'         => $data['price'],
            ':delivery_days' => $data['delivery_days'],
            ':image_path'    => $data['image_path']     ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, int $freelancerId, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE services
             SET    category_id   = :category_id,
                    title         = :title,
                    description   = :description,
                    price         = :price,
                    delivery_days = :delivery_days,
                    status        = :status
                    {$data['image_sql']}
             WHERE  id            = :id
               AND  freelancer_id = :fid"
        );

        $params = [
            ':category_id'   => $data['category_id'] ?: null,
            ':title'         => $data['title'],
            ':description'   => $data['description'],
            ':price'         => $data['price'],
            ':delivery_days' => $data['delivery_days'],
            ':status'        => $data['status'],
            ':id'            => $id,
            ':fid'           => $freelancerId,
        ];

        if (!empty($data['image_path'])) {
            $params[':image_path'] = $data['image_path'];
        }

        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /** Soft-delete: sets status = 'deleted' and clears image reference. */
    public function softDelete(int $id, int $freelancerId): string|false
    {
        // Fetch image path before deleting so caller can remove the file
        $row = $this->getOne($id, $freelancerId);
        if (!$row) {
            return false;
        }

        $this->pdo->prepare(
            "UPDATE services
             SET status = 'deleted', image_path = NULL
             WHERE id = :id AND freelancer_id = :fid"
        )->execute([':id' => $id, ':fid' => $freelancerId]);

        return $row['image_path'] ?? '';
    }

    /** Count active services for a freelancer (for dashboard badge). */
    public function countActive(int $freelancerId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM services
             WHERE freelancer_id = :fid AND status = 'active'"
        );
        $stmt->execute([':fid' => $freelancerId]);
        return (int) $stmt->fetchColumn();
    }
}
