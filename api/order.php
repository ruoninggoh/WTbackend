<?php
require_once './config.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->post('/addOrder', function (Request $request, Response $response, $args) {
    $db = new db();
    $con = $db->connect();
    
    // Process form data
    $formData = $request->getParsedBody();
    error_log("Form data: " . json_encode($formData));

    // Extract form fields
    $customer_name = $formData['customer_name'] ?? null;
    $payment_method = $formData['payment_method'] ?? null;
    $delivery_method = $formData['delivery_method'] ?? null;
    $caddress = $formData['address'] ?? null;
    $phone_num = $formData['phone_num'] ?? null;
    $order = json_decode($formData['order'] ?? '[]', true);

    // Log extracted data
    error_log("Customer Name: $customer_name");
    error_log("Payment Method: $payment_method");
    error_log("Delivery Method: $delivery_method");
    error_log("Address: $caddress");
    error_log("Phone Number: $phone_num");
    error_log("Order: " . json_encode($order));

    try {
        // Begin transaction
        $con->beginTransaction();

       // Your SQL query to retrieve the ID of the last row in the "order" table
        $sql = "SELECT MAX(order_id) AS max_order_id FROM orders";

        // Prepare and execute the SQL statement
        $stmt = $con->prepare($sql);
        $stmt->execute();

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get the ID of the last row
        $order_id= $result['max_order_id']+1;
        error_log("orderid: " .  $order_id );
        // Insert into order_items table
        $sql = "INSERT INTO order_items (order_ID, food_ID, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $con->prepare($sql);

        foreach ($order as $item) {
            error_log("Inserting item: " . json_encode($item));
            $stmt->execute([$order_id, $item['FoodID'], $item['quantity'], $item['subprice']]);
        }

        // Insert into Orders table
        $current_time = date('Y-m-d H:i:s');
        error_log("current_time: " .  $current_time);
        error_log("Phone Number: $phone_num");
        $sql = "INSERT INTO Orders (order_ID, customer_name, payment_method, delivery_method, address, phone_num,cdate) 
                VALUES (?, ?, ?, ?, ?, ?,?)";
        $stmt = $con->prepare($sql);
        $stmt->execute([$order_id, $customer_name, $payment_method, $delivery_method, $caddress, $phone_num, $current_time]);

        // Commit transaction
        $con->commit();

        // Return a JSON response
        $responseBody = json_encode(['success' => true, 'message' => 'Order added successfully']);
        $response->getBody()->write($responseBody);
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        // Rollback transaction if something went wrong
        $con->rollBack();

        $errorMessage = $e->getMessage();
        error_log("Failed to add order: $errorMessage");

        $responseBody = json_encode(['success' => false, 'message' => 'Failed to add order: ' . $errorMessage]);
        $response->getBody()->write($responseBody);
        return $response->withHeader('Content-Type', 'application/json');
    }
});
