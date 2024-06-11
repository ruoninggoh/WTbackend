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
    try{
        $db = new db();
        $con = $db->connect();
        
        // Process form data
        $formData = $request->getParsedBody();
    
        // Extract form fields
        $foodName = $formData['foodName'];
        $foodDescription = $formData['foodDescription'];
        $foodPrice = $formData['foodPrice'];
        $foodAvailability = $formData['foodAvailability']; 
    
        if(isset($_FILES['foodImg']['name'])){
            //get selected image name
            $foodImgName = $_FILES['foodImg']['name'];
            
            if($foodImgName!=""){
                $foodImgName = explode('.', $foodImgName);
                $ext = end($foodImgName);
    
                $foodImgName = "Food-".rand(0000,9999).".".$ext;
    
                $src=$_FILES['foodImg']['tmp_name'];
    
                $dst = "../../frontend/src/assets/image/food/".$foodImgName;
    
                $upload = move_uploaded_file($src, $dst);
                
            }
    
        } else {
            $foodImgName = "";
        }
    
    
        $sql = "INSERT INTO Menu (FoodName, FoodDescription, FoodImg, FoodPrice, FoodAvailability) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->execute([$foodName, $foodDescription, $foodImgName, $foodPrice, $foodAvailability]);

        if ($stmt->rowCount() > 0) {
            // Return a JSON response instead of redirecting
            $responseBody = json_encode(['status' => 'success', 'message' => 'Menu added successfully']);
            return $response->withHeader('Content-Type', 'application/json')->write($responseBody);
        } else {
            $responseBody = json_encode(['status' => 'error', 'message' => 'Failed to add menu']);
            return $response->withHeader('Content-Type', 'application/json')->write($responseBody);
        }

    } catch (Exception $e) {
        return $response->withJson(['status' => 'error', 'message' => 'Failed to add menu']);
    }
    
});

$app->post('/updateMenu', function (Request $request, Response $response) {
    try{
        $db = new db();
        $con = $db->connect();

        $formData = $request->getParsedBody();

        $id = $formData['id'];
        $name = $formData['foodName'];
        $currentImg = $formData['currentImg'];
        $description = $formData['foodDescription'];
        $price = $formData['foodPrice'];
        $availability = $formData['foodAvailability'];

        if(isset($_FILES['foodImg']['name'])){
            $foodImg = $_FILES['foodImg']['name'];

            if($foodImg!=""){
                //upload new image
                $foodImg = explode('.', $foodImg);
                $ext = end($foodImg);
                $foodImg = "Food-".rand(0000, 9999).'.'.$ext;
                $src_path = $_FILES['foodImg']['tmp_name'];
                $des_path = "../../frontend/src/assets/image/food/".$foodImg;

                $upload = move_uploaded_file($src_path, $des_path);

                //remove current image
                if ($currentImg) {
                    $path = "../../frontend/src/assets/image/food/$currentImg";
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }

            } else {
                $foodImg = $currentImg;
            }

        } else {
            $foodImg = $currentImg;
        }
        
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
        return $response->withJson(['status' => 'success', 'message' => 'Food updated successfully']);
    } catch(Exception $e){
        return $response->withJson(['status' => 'error', 'message' => 'Failed to update food']);
    }
});

$app->delete('/deleteMenu/{foodId}', function (Request $request, Response $response, $args) {
    try{
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
            
            return $response->withJson(['status' => 'success', 'message' => 'Menu deleted successfully']);
        } else {
            
            return $response->withJson(['status' => 'error', 'message' => 'Failed to delete food']);
        }
    } catch (Exception $e){
        return $response->withJson(['status' => 'error', 'message' => 'Failed to delete food']);
    }
    
});

?>