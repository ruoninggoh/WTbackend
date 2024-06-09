<?php 
require_once './config.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/menuList', function (Request $request, Response $response, $args) {
    $db = new db();
    $con = $db->connect();
    try {
        $query = "SELECT * FROM menu";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $menu = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $response->getBody()->write(json_encode($menu));
    } catch (PDOException $e) {
        $error = [
            "message" => $e->getMessage()
        ];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->post('/addMenu', function (Request $request, Response $response, $args) {
    $db = new db();
    $con = $db->connect();
    
    // Process form data
    $formData = $request->getParsedBody();

    // Extract form fields
    $foodName = $formData['foodName'];
    $foodDescription = $formData['foodDescription'];
    $foodPrice = $formData['foodPrice'];
    $foodAvailability = $formData['foodAvailability']; // No need for isset as it's always sent from the frontend

    // Handle file upload
    $uploadedFile = $request->getUploadedFiles()['foodImg'] ?? null;
    if ($uploadedFile !== null && $uploadedFile->getError() === UPLOAD_ERR_OK) {
        $filename = $uploadedFile->getClientFilename();
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $foodImgName = "Food-" . uniqid() . "." . $ext;
        $uploadedFile->moveTo(__DIR__ . "/../../assets/image/food/$foodImgName"); // Corrected path
    } else {
        $foodImgName = '';
    }

    $sql = "INSERT INTO Menu (FoodName, FoodDescription, FoodImg, FoodPrice, FoodAvailability) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->execute([$foodName, $foodDescription, $foodImgName, $foodPrice, $foodAvailability]);

    if ($stmt->rowCount() > 0) {
        // Return a JSON response instead of redirecting
        $responseBody = json_encode(['success' => true, 'message' => 'Menu added successfully']);
        return $response->withHeader('Content-Type', 'application/json')->write($responseBody);
    } else {
        $responseBody = json_encode(['success' => false, 'message' => 'Failed to add menu']);
        return $response->withHeader('Content-Type', 'application/json')->write($responseBody);
    }
});

$app->post('/updateMenu', function (Request $request, Response $response) {
    $db = new db();
    $con = $db->connect();

    $formData = $request->getParsedBody();

    $id = $formData['id'];
    $name = $formData['foodName'];
    $currentImg = $formData['currentImg'];
    $description = $formData['foodDescription'];
    $price = $formData['foodPrice'];
    $availability = $formData['foodAvailability'];

    // Handle file upload
    $uploadedFile = $request->getUploadedFiles()['foodImg'] ?? null;
    if ($uploadedFile !== null && $uploadedFile->getError() === UPLOAD_ERR_OK) {
        $filename = $uploadedFile->getClientFilename();
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $foodImg = "Food-" . uniqid() . "." . $ext;
        $uploadedFile->moveTo(__DIR__ . "/../../assets/image/food/$foodImg"); // Corrected path

        if ($currentImg) {
            $path = "../../frontend/src/assets/image/food/$currentImg";
            if (file_exists($path)) {
                unlink($path);
            }
        }
    } else {
        $foodImg = $currentImg;
    }

    // Proceed with your database update logic using an ORM or direct query execution
    $sql = "UPDATE menu SET 
            FoodName = :name,
            FoodDescription = :description,
            FoodImg = :foodImg,
            FoodPrice = :price,
            FoodAvailability = :availability
            WHERE FoodID = :id";
    $stmt = $con->prepare($sql);
    $stmt->execute([
        'name' => $name,
        'description' => $description,
        'foodImg' => $foodImg,
        'price' => $price,
        'availability' => $availability,
        'id' => $id
    ]);

    // Return a response based on the outcome
    if ($stmt->rowCount() > 0) {
        $responseBody = json_encode(['success' => true, 'message' => 'Food updated successfully']);
        return $response->withHeader('Content-Type', 'application/json')->getBody()->write($responseBody);
    } else {
        $responseBody = json_encode(['success' => false, 'message' => 'Failed to update food']);
        return $response->withHeader('Content-Type', 'application/json')->getBody()->write($responseBody);
    }
});

$app->delete('/deleteMenu/{foodId}', function (Request $request, Response $response, $args) {
    $foodId = $args['foodId'];

    $db = new db();
    $con = $db->connect();

    $sql = "SELECT FoodImg FROM menu WHERE FoodID = ?";
    $stmt = $con->prepare($sql);
    $stmt->execute([$foodId]);
    $foodImg = $stmt->fetchColumn();

    if ($foodImg) {
        $path = "../../frontend/src/assets/image/food/$foodImg";
        if (file_exists($path)) {
            unlink($path);
        }
    }

    $sql = "DELETE FROM menu WHERE FoodID = ?";
    $stmt = $con->prepare($sql);
    $result = $stmt->execute([$foodId]);

    if ($result) {
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Menu deleted successfully'
        ]));
    } else {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => 'Failed to delete'
        ]));
    }
});

?>