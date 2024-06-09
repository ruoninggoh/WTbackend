<?php 
require_once './config.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/userManageList', function (Request $request, Response $response, $args) {
    $params = $request->getQueryParams();
    
    $sort_method = $params['sort'] ?? null;
    $search_val = $params['search_val'] ?? null;

    $db = new db();
    $con = $db->connect();

    try {
        if ($sort_method && $search_val) {
            if ($sort_method == "sortname") {
                $query = "SELECT * FROM users WHERE Username=:searchVal ORDER BY Username";
            } elseif ($sort_method == "sortrole") {
                $query = "SELECT * FROM users WHERE Username=:searchVal ORDER BY cRole";
            } else {
                $query = "SELECT * FROM users WHERE Username=:searchVal";
            }
            $stmt = $con->prepare($query);
            $stmt->bindValue("searchVal", $search_val);
        } elseif ($sort_method) {
            if ($sort_method == "sortname") {
                $query = "SELECT * FROM users ORDER BY Username";
            } elseif ($sort_method == "sortrole") {
                $query = "SELECT * FROM users ORDER BY cRole";
            } else {
                $query = "SELECT * FROM users";
            }
            $stmt = $con->prepare($query);
        } elseif ($search_val) {
            if ($search_val == "") {
                $query = "SELECT * FROM users";
                $stmt = $con->prepare($query);
            } else {
                $query = "SELECT * FROM users WHERE Username=:searchVal";
                $stmt = $con->prepare($query);
                $stmt->bindValue("searchVal", $search_val);
            }
        } else {
            $query = "SELECT * FROM users";
            $stmt = $con->prepare($query);
        }

        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $response->getBody()->write(json_encode($users));
    } catch (PDOException $e) {
        $error = [
            "message" => $e->getMessage()
        ];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }

    // return $response->withHeader('Content-Type', 'application/json');
});
?>