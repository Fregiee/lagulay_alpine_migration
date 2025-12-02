<?php
session_start();
require 'dbconfig.php';

header('Content-Type: application/json');

$response = ["success" => false, "message" => "Unknown error"];

try {

    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    $action = $input['action'] ?? $_POST['action'] ?? '';

    switch ($action) {

        
        case 'register':
            $username = trim($input['username'] ?? '');
            $password = trim($input['password'] ?? '');
            $type = 1;
            $suspension = 0;

            if ($username === '' || $password === '') {
                $response['message'] = "Username and password cannot be empty";
                break;
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $response['message'] = "Username already taken";
                break;
            }

            $stmt = $pdo->prepare("INSERT INTO users (username, password, type, suspension) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password, $type, $suspension]);

            $response = ['success' => true, 'message' => 'Registration successful'];
            break;

        case 'login':
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';

            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['password'] === $password) {
                if ((int)$user['suspension'] === 1) {
                    $response['message'] = 'This account is suspended';
                    break;
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['type'] = $user['type'];

                $response = [
                    'success' => true,
                    'message' => 'Login successful!',
                    'type' => (int)$user['type']
                ];
            } else {
                $response['message'] = 'Invalid username or password';
            }
            break;

        case 'logout':
            session_destroy();
            $response = ['success' => true, 'message' => 'Logout successful'];
            break;

        
        case 'addProduct':
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = "User not logged in";
                break;
            }

            $name = $_POST['name'] ?? '';
            $price = $_POST['price'] ?? '';
            $added_by = $_SESSION['user_id'];
            $image = $_FILES['image'] ?? null;

            if ($name === '' || $price === '' || !$image) {
                $response['message'] = "All fields are required";
                break;
            }

            $uploadDir = '../uploads/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

            $imageName = time() . '_' . basename($image['name']);
            $uploadPath = $uploadDir . $imageName;

            if (move_uploaded_file($image['tmp_name'], $uploadPath)) {
                $stmt = $pdo->prepare("INSERT INTO products (name, image, price, added_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $imageName, $price, $added_by]);
                $response = ['success' => true, 'message' => 'Product added successfully'];
            } else {
                $response['message'] = "Failed to upload image";
            }
            break;

        case 'get_products': 
            $stmt = $pdo->query("SELECT p.*, u.username AS added_by
                FROM products p
                LEFT JOIN users u ON p.added_by = u.id
                ORDER BY p.id DESC");
            $response = ['success' => true, 'products' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            break;

        case 'editProduct':
            $id = $input['id'] ?? 0;
            $name = $input['name'] ?? '';
            $price = $input['price'] ?? '';

            if ($id == 0 || $name === '' || $price === '') {
                $response['message'] = "All fields are required";
                break;
            }

            $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ? WHERE id = ?");
            $stmt->execute([$name, $price, $id]);

            $response = ['success' => true, 'message' => 'Product updated successfully'];
            break;

        case 'deleteProduct':
            $id = $input['id'] ?? 0;
            if (!$id) {
                $response['message'] = "Invalid product ID";
                break;
            }

            $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product && file_exists("../uploads/" . $product['image'])) {
                unlink("../uploads/" . $product['image']);
            }

            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $response = ['success' => true, 'message' => 'Product deleted successfully'];
            break;

        
        case 'place_order':
            $customerId = $_SESSION['user_id'] ?? 0;
            $money = (int)($input['money'] ?? 0);
            $product_list = trim($input['product_list'] ?? '');

            if ($customerId === 0 || $money <= 0 || $product_list === '') {
                $response['message'] = "Invalid order data";
                break;
            }

            $stmt = $pdo->prepare("INSERT INTO orders (customerId, money, product_list, status) VALUES (?, ?, ?, 'Pending')");
            $stmt->execute([$customerId, $money, $product_list]);
            $response = ['success' => true, 'message' => 'Order placed successfully'];
            break;

        case 'get_orders':
            $customerId = $_SESSION['user_id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE customerId = ? ORDER BY id DESC");
            $stmt->execute([$customerId]);
            $response = ['success' => true, 'orders' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            break;

        case 'get_transactions':
            $from = $input['fromDate'] ?? '';
            $to = $input['toDate'] ?? '';
            $query = "SELECT * FROM transactions";
            $params = [];

            if (!empty($from) && !empty($to)) {
                $query .= " WHERE DATE(date_added) BETWEEN ? AND ?";
                $params = [$from, $to];
            }

            $query .= " ORDER BY date_added DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            $response = ['success' => true, 'transactions' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            break;

        //admin
        case 'get_admin_users':
            $stmt = $pdo->prepare("SELECT id, username, suspension, type FROM users WHERE type IN (2,3) ORDER BY id DESC");
            $stmt->execute();
            $response = ['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            break;


        case 'update_user_suspension':
            $id = (int)($input['id'] ?? 0);
            $suspension = (int)($input['suspension'] ?? 0);

            if (!$id) {
                $response['message'] = 'Invalid user ID';
                break;
            }

            $stmt = $pdo->prepare("UPDATE users SET suspension = ? WHERE id = ?");
            $stmt->execute([$suspension, $id]);

            $response = [
                'success' => true,
                'message' => $suspension == 1 ? 'User suspended' : 'User activated'
            ];
            break;
        
        case 'get_pending_orders':
            $stmt = $pdo->prepare("
                SELECT 
                    o.id,
                    o.money,
                    o.status,
                    o.product_list,
                    GROUP_CONCAT(p.name SEPARATOR ', ') AS product_names
                FROM orders o
                LEFT JOIN products p ON FIND_IN_SET(p.id, o.product_list)
                WHERE o.status = 'Pending'
                GROUP BY o.id
                ORDER BY o.id DESC
            ");
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($orders) {
                $response = ['success' => true, 'orders' => $orders];
            } else {
                $response = ['success' => false, 'message' => 'No pending orders found', 'orders' => []];
            }
            break;


        case 'update_order_status':
            $id = (int)($input['id'] ?? 0);
            $status = strtoupper(trim($input['status'] ?? ''));

            if (!$id || !in_array($status, ['APPROVED', 'DECLINED'])) {
                $response['message'] = 'Invalid order data';
                break;
            }

            // Update the order status
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);

            // Fetch order details
            $stmt = $pdo->prepare("SELECT customerId, product_list FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // Use session user 
                $adminId = $_SESSION['user_id'] ?? 0;


               $insert = $pdo->prepare("
                        INSERT INTO transactions (adminId, customerId, product_list, date_added)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $insert->execute([
                        $adminId,
                        $order['customerId'],
                        $order['product_list']
                    ]);
            }

            $response = ['success' => true, 'message' => "Order has been {$status}"];
            break;



        // super
        case 'addUser':
            $username = trim($input['username'] ?? '');
            $password = trim($input['password'] ?? '');
            $type = (int)($input['type'] ?? 1);

            if ($username === '' || $password === '' || !in_array($type, [1, 2, 3])) {
                $response['message'] = 'Invalid input data';
                break;
            }

            // Check existing user
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                $response['message'] = 'Username already exists';
                break;
            }

            $stmt = $pdo->prepare("INSERT INTO users (username, password, type, suspension) VALUES (?, ?, ?, 0)");
            $stmt->execute([$username, $password, $type]);

            $response = ['success' => true, 'message' => 'User added successfully'];
            break;
        
        default:
            $response['message'] = "Invalid action";
    }

} catch (PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = "Error: " . $e->getMessage();
}

echo json_encode($response);
exit;
