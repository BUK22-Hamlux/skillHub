<?php
// marketplace/MarketplaceModel.php
// Read-only queries for the public service marketplace.

declare(strict_types=1);

class MarketplaceModel
{
    private const PER_PAGE = 12;

    public function __construct(private PDO $pdo) {}

    /**
     * Browse active services with optional search + category filter.
     * Returns ['services' => [...], 'total' => int, 'pages' => int]
     */
    public function browse(array $filters = [], int $page = 1): array
    {
        $where  = ["s.status = 'active'"];
        $params = [];

        if (!empty($filters['q'])) {
            $where[]        = "(s.title LIKE :q OR s.description LIKE :q OR u.full_name LIKE :q)";
            $params[':q']   = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['category_id'])) {
            $where[]             = "s.category_id = :cat";
            $params[':cat']      = (int) $filters['category_id'];
        }

        if (!empty($filters['max_price'])) {
            $where[]             = "s.price <= :max_price";
            $params[':max_price'] = (float) $filters['max_price'];
        }

        if (!empty($filters['max_days'])) {
            $where[]            = "s.delivery_days <= :max_days";
            $params[':max_days'] = (int) $filters['max_days'];
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        // Total count
        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM services s
             JOIN   users u ON u.id = s.freelancer_id
             {$whereSQL}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = max(1, min($page, $pages));
        $offset = ($page - 1) * self::PER_PAGE;

        // Sort
        $sortMap = [
            'newest'    => 's.created_at DESC',
            'price_asc' => 's.price ASC',
            'price_desc'=> 's.price DESC',
            'delivery'  => 's.delivery_days ASC',
        ];
        $sort = $sortMap[$filters['sort'] ?? ''] ?? 's.created_at DESC';

        $stmt = $this->pdo->prepare(
            "SELECT s.*,
                    sc.name     AS category_name,
                    u.full_name AS freelancer_name,
                    u.id        AS freelancer_user_id
             FROM   services s
             JOIN   users u         ON u.id  = s.freelancer_id
             LEFT JOIN service_categories sc ON sc.id = s.category_id
             {$whereSQL}
             ORDER  BY {$sort}
             LIMIT  :limit OFFSET :offset"
        );

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit',  self::PER_PAGE, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,         PDO::PARAM_INT);
        $stmt->execute();

        return [
            'services'  => $stmt->fetchAll(),
            'total'     => $total,
            'pages'     => $pages,
            'page'      => $page,
            'per_page'  => self::PER_PAGE,
        ];
    }

    /** Single public service detail (active only). */
    public function getService(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*,
                    sc.name          AS category_name,
                    u.full_name      AS freelancer_name,
                    u.id             AS freelancer_user_id,
                    fp.headline      AS freelancer_headline,
                    fp.skills        AS freelancer_skills,
                    fp.availability  AS freelancer_availability
             FROM   services s
             JOIN   users u              ON u.id  = s.freelancer_id
             LEFT JOIN service_categories sc  ON sc.id = s.category_id
             LEFT JOIN freelancer_profiles fp ON fp.user_id = s.freelancer_id
             WHERE  s.id = :id AND s.status = 'active'
             LIMIT  1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /** Other active services by the same freelancer (excluding current). */
    public function getRelated(int $serviceId, int $freelancerId, int $limit = 3): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*, sc.name AS category_name
             FROM   services s
             LEFT JOIN service_categories sc ON sc.id = s.category_id
             WHERE  s.freelancer_id = :fid
               AND  s.id != :sid
               AND  s.status = 'active'
             ORDER  BY s.created_at DESC
             LIMIT  :lim"
        );
        $stmt->bindValue(':fid', $freelancerId, PDO::PARAM_INT);
        $stmt->bindValue(':sid', $serviceId,    PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit,        PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCategories(): array
    {
        return $this->pdo
            ->query('SELECT id, name FROM service_categories ORDER BY name')
            ->fetchAll();
    }
}
