<?php 
require_once './config.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/orderManageList', function (Request $request, Response $response, $args) {
    $params = $request->getQueryParams();

    $filter = $params['filter'] ?? null;
    $search_val = $params['search_val'] ?? null;

    $db = new db();
    $con = $db->connect();

    try {
        if ($filter && $search_val) {

            $query = "SELECT * FROM orders WHERE order_ID = :searchVal OR customer_name=:searchVal AND order_status=:order_status ORDER BY cdate DESC";
            $searchPattern = "%" . $searchVal . "%";
            $stmt = $con->prepare($query);
            $stmt->bindValue("searchVal", $searchPattern);
            $stmt->bindValue("order_status", $filter);

        } elseif ($filter) {
            $query = "SELECT * FROM orders WHERE order_status=:order_status ORDER BY cdate DESC";
            $stmt = $con->prepare($query);
            $stmt->bindValue("order_status", $filter);
        } elseif ($search_val) {
            if ($search_val == "") {
                $query = "SELECT * FROM orders ORDER BY cdate DESC";
                $stmt = $con->prepare($query);
            } else {
                $query = "SELECT * FROM orders WHERE order_ID = :searchVal OR customer_name=:searchVal ORDER BY cdate DESC";
                $searchPattern = "%" . $searchVal . "%";
                $stmt = $con->prepare($query);
                $stmt->bindValue("searchVal", $searchPattern);
            }
        } else {
            $query = "SELECT * FROM orders ORDER BY cdate DESC";
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

        return $response->withJson(['status' => 'success',
                'message' => 'Order Updated Successfully']);

    } catch (PDOException $e) {
        return $response->withJson(['status' => 'error',
            'message' => $e->getMessage()]);
    }

    return $response->withHeader('Content-Type', 'application/json');
});

?>