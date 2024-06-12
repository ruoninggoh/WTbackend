<?php
require_once './config.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


$app->post('/login', function (Request $request, Response $response, $args) {
    $data = $request->getParsedBody();
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    // Validate input
    if (empty($email) || empty($password)) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid input']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // Connect to the database
    $db = new db();
    $dbConnection = $db->connect();

    if (!$dbConnection) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Database connection failed']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    $encrypted_password = md5($password);

    // Check if the user exists
    $stmt = $dbConnection->prepare("SELECT * FROM users WHERE Email = ? AND cPassword = ?");
    $stmt->execute([$email, $encrypted_password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $response->getBody()->write(json_encode(['success' => true, 'role' => $user['cRole']]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Wrong Email or Password']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// $app->run();

?>
