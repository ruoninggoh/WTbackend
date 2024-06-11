<?php
require_once './config.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// use Slim\Factory\AppFactory;

// require __DIR__ . '/../vendor/autoload.php';

// $app = AppFactory::create();

// $app->addBodyParsingMiddleware();

$app->post('/register', function (Request $request, Response $response, $args) {
    $data = $request->getParsedBody();
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
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

    // Verify unique email
    $stmt = $dbConnection->prepare("SELECT Email FROM users WHERE Email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'This email is already used. Try another one!']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // Insert user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $dbConnection->prepare("INSERT INTO users (Username, Email, cPassword, cRole) VALUES (?, ?, ?, 'customer')");
    if ($stmt->execute([$username, $email, $hashedPassword])) {
        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Registration successful!']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $errorInfo = $stmt->errorInfo();
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Error occurred during registration: ' . $errorInfo[2]]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// $app->run();

?>
