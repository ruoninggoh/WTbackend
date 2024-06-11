<?php
require_once './config.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/orders/{user_id}', function (Request $request, Response $response, $args) {
    $db = new db();
    $con = $db->connect();
    $user_id = $args['user_id'];
    // $user_id = 2;  
    
    try {
        $query = "SELECT o.*, u.Username, GROUP_CONCAT(oi.food_ID) as food_IDs, GROUP_CONCAT(oi.quantity) as quantities, GROUP_CONCAT(oi.price) as prices
                  FROM `orders` o
                  JOIN `users` u ON o.user_ID = u.user_ID
                  LEFT JOIN `order_items` oi ON o.order_ID = oi.order_ID
                  WHERE u.user_ID = :user_id
                  GROUP BY o.order_ID";
        $stmt = $con->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as &$order) {
            $food_IDs = explode(',', $order['food_IDs']);
            $quantities = explode(',', $order['quantities']);
            $prices = explode(',', $order['prices']);

            $items = [];
            $total_price = 0;
            foreach ($food_IDs as $index => $food_ID) {
                $foodQuery = "SELECT * FROM `menu` WHERE `FoodID` = :food_ID";
                $foodStmt = $con->prepare($foodQuery);
                $foodStmt->bindParam(':food_ID', $food_ID, PDO::PARAM_INT);
                $foodStmt->execute();
                $food = $foodStmt->fetch(PDO::FETCH_ASSOC);

                $food['quantity'] = $quantities[$index];
                $food['price'] = number_format($prices[$index], 2);

                $items[] = $food;

            }
            $order['items'] = $items;
          
            $order['payment_method'] = $order['payment_method'];  
        }

        $response->getBody()->write(json_encode($orders));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        $error = ["message" => "Database error: " . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});
?>
