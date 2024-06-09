<?php 
require_once './config.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/orderManageList', function (Request $request, Response $response, $args) {
    $params = $request->getQueryParams();

    $sort_method = $params['sort'] ?? null;
    $search_val = $params['search_val'] ?? null;

    $db = new db();
    $con = $db->connect();

    try {
        if ($sort_method && $search_val) {
            if ($sort_method == "sortname") {
                $query = "SELECT * FROM orders WHERE order_ID = :searchVal OR customer_name=:searchVal ORDER BY customer_name";
            } else if ($sort_method == "sortdate") {
                $query = "SELECT * FROM orders WHERE order_ID = :searchVal OR customer_name=:searchVal ORDER BY cdate DESC";
            } else {
                $query = "SELECT * FROM orders WHERE order_ID = :searchVal OR customer_name=:searchVal";
            }
            $stmt = $con->prepare($query);
            $stmt->bindValue("searchVal", $search_val);
        } elseif ($sort_method) {
            if ($sort_method == "sortname") {
                $query = "SELECT * FROM orders ORDER BY customer_name";
            } elseif ($sort_method == "sortdate") {
                $query = "SELECT * FROM orders ORDER BY cdate DESC";
            } else {
                $query = "SELECT * FROM orders";
            }
            $stmt = $con->prepare($query);
        } elseif ($search_val) {
            if ($search_val == "") {
                $query = "SELECT * FROM orders";
                $stmt = $con->prepare($query);
            } else {
                $query = "SELECT * FROM orders WHERE order_ID = :searchVal OR customer_name=:searchVal";
                $stmt = $con->prepare($query);
                $stmt->bindValue("searchVal", $search_val);
            }
        } else {
            $query = "SELECT * FROM orders";
            $stmt = $con->prepare($query);
        }

        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_OBJ);

        $response->getBody()->write(json_encode($orders));
    } catch (PDOException $e) {
        $error = [
            "message" => $e->getMessage()
        ];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }

    // return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/getOrderById/{orderId}', function (Request $request, Response $response, $args) {
    $orderId = $args['orderId'];
    $db = new db();
    $con = $db->connect();

    try {
        $q1 = "SELECT * FROM orders WHERE order_ID=:orderId";
        $stmt = $con->prepare($q1);
        $stmt->bindValue("orderId", $orderId);
        $stmt->execute();
        $orderInfo = $stmt->fetchAll(PDO::FETCH_OBJ);

        $q2 = "SELECT orders.order_ID, order_items.quantity, order_items.price, menu.FoodName, menu.FoodPrice 
        FROM orders 
        JOIN order_items ON orders.order_ID=order_items.order_ID 
        JOIN menu ON menu.FoodID=order_items.food_ID 
        WHERE order_items.order_ID=:orderId";
        $stmt2 = $con->prepare($q2);
        $stmt2->bindValue("orderId", $orderId);
        $stmt2->execute();
        $orderItems = $stmt2->fetchAll(PDO::FETCH_OBJ);

        $orderDetails = [
            'orderInfo' => $orderInfo,
            'orderItems' => $orderItems
        ];

        $response->getBody()->write(json_encode($orderDetails));

    } catch (PDOException $e) {
        $error = [
            "message" => $e->getMessage()
        ];
        $response->getBody()->write(json_encode($error));
    }
});

$app->put('/updateOrder/{orderId}', function (Request $request, Response $response, array $args) {
    $orderId = $args['orderId'];
    $params = (array)$request->getParsedBody();

    $status = $params['order_status'];
    $cus_name = $params['customer_name'];
    $contact = $params['phone_num'];
    $address = $params['address'];

    $db = new db();
    $con = $db->connect();

    try {
        $q3 = "UPDATE orders SET 
                order_status = ?,
                customer_name = ?,
                phone_num = ?,
                address = ?
                WHERE order_ID = ?";
        $stmt = $con->prepare($q3);
        $stmt->execute([$status, $cus_name, $contact, $address, $orderId]);

        if ($stmt->rowCount() > 0) {
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Order Updated Successfully'
            ]));
        } else {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Failed to Update Order'
            ]));
        }
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }

    return $response->withHeader('Content-Type', 'application/json');
});

?>