<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');
session_start();

try {
    $search = $_GET['search'] ?? '';
    $category_id = $_GET['category_id'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 12;
    $offset = ($page - 1) * $limit;
    
    // Build query
    $where_conditions = ["i.show_on_website = 1", "i.status = 'active'"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(i.item_name LIKE ? OR i.item_description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($category_id)) {
        $where_conditions[] = "i.category_id = ?";
        $params[] = $category_id;
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Get total count
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM inventory i 
        WHERE $where_clause
    ");
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetch()['total'];
    
    // Get products
    $stmt = $pdo->prepare("
        SELECT i.item_id, i.item_name, i.item_description, i.customer_price, 
               i.unit_price, i.current_stock, i.show_on_website, i.status,
               c.category_name, c.category_id
        FROM inventory i
        LEFT JOIN categories c ON i.category_id = c.category_id
        WHERE $where_clause
        ORDER BY i.item_name ASC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Get categories for filter
    $cat_stmt = $pdo->prepare("
        SELECT category_id, category_name 
        FROM categories 
        WHERE status = 'active' 
        ORDER BY category_name
    ");
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll();
    
    echo json_encode([
        'status' => 'success',
        'products' => $products,
        'categories' => $categories,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total_count / $limit),
            'total_items' => $total_count,
            'items_per_page' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get Products Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 