<?php 
require_once './config.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/total_menus', function (Request $request, Response $response, $args) {
    $db = new db();
    $con = $db->connect();
    try {
        $query = "SELECT COUNT(*) as total FROM menu";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        $response->getBody()->write(json_encode($result->total));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $error = ["message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/average_sales_permenu', function (Request $request, Response $response, $args) {
    $db = new db();
    $con = $db->connect();
    try {
        // Query to calculate the total sales per menu item
        $query = "SELECT m.FoodID, COALESCE(SUM(oi.quantity * oi.price), 0) AS total_sales
                  FROM menu m
                  LEFT JOIN order_items oi ON m.FoodID = oi.food_ID
                  GROUP BY m.FoodID";

        $stmt = $con->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Initialize variables to calculate total sales and the number of menu items
        $totalSales = 0;
        $menuCount = count($results);

        // Sum the total sales for all menu items
        foreach ($results as $menu) {
            $totalSales += $menu->total_sales;
        }

        // Calculate the average sales per menu item
        $averageSalesPerMenu = 0;
        if ($menuCount > 0) {
            $averageSalesPerMenu = $totalSales / $menuCount;
        }

        // Prepare response with the calculated average
        $responseData = [
            "averageSalesPerMenu" => $averageSalesPerMenu
        ];

        // Send the response as JSON
        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        // Handle any errors
        $error = ["message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});





$app->get('/total_orders', function (Request $request, Response $response, $args) {
    $db = new db();
    $con = $db->connect();
    try {
        // Get the current date
        $currentDate = date("Y-m-d");

        // Query to count total orders for today
        $query = "SELECT COUNT(*) as total_orders FROM orders WHERE DATE(cdate) = :current_date";

        $stmt = $con->prepare($query);
        $stmt->bindParam(':current_date', $currentDate);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        $response->getBody()->write(json_encode($result->total_orders));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $error = ["message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});


$app->get('/total_sales', function (Request $request, Response $response, $args) {
    $db = new db();
    $con = $db->connect();
    try {
        $query = "SELECT SUM(price * quantity) as total_sales FROM order_items";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        $response->getBody()->write(json_encode($result->total_sales));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $error = ["message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/total_customers', function (Request $request, Response $response, $args) {
    $db = new db();
    $con = $db->connect();
    try {
        $query = "SELECT COUNT(*) as total_customers FROM users WHERE cRole = 'customer'";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        $response->getBody()->write(json_encode($result->total_customers));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $error = ["message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/popular_food', function (Request $request, Response $response, $args) {
    $db = new db();
    $con = $db->connect();
    try {
        $query = "SELECT menu.FoodName as name, SUM(order_items.quantity) as quantity 
                  FROM order_items 
                  JOIN menu ON order_items.food_ID = menu.FoodID 
                  GROUP BY order_items.food_ID 
                  ORDER BY quantity DESC
                  LIMIT 2"; // Limit the result to only the top two records
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Check if there are more than two popular food items
        if (count($result) > 2) {
            // Add a new object for "Others" with the total quantity of other items
            $otherQuantity = 0;
            foreach ($result as $food) {
                $otherQuantity += $food->quantity;
            }
            $others = new stdClass();
            $others->name = "Others";
            $others->quantity = $otherQuantity;
            // Remove the third and subsequent items from the result array
            array_splice($result, 2);
            // Push the "Others" object into the result array
            array_push($result, $others);
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $error = ["message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});



$app->get('/order_summary', function (Request $request, Response $response, $args) {
    $db = new db();
    $con = $db->connect();
    try {
        // Example query to get orders per month
        $query = "SELECT DATE_FORMAT(cdate, '%Y-%m') as month, COUNT(*) as total_orders 
                  FROM orders 
                  GROUP BY month 
                  ORDER BY month";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Format the response to be used in chart.js
        $responseData = [
            "labels" => array_map(fn($r) => $r->month, $result),
            "values" => array_map(fn($r) => $r->total_orders, $result)
        ];

        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $error = ["message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

?>