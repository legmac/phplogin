<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require __DIR__ . '/vendor/autoload.php';

$loader = new FilesystemLoader('templates');
$view = new Environment($loader);

$config = include 'config/database.php';
$dsn = $config['dsn'];
$username = $config['username'];
$password = $config['password'];

try {
    $connection = new PDO($dsn, $username, $password);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    echo 'Database error: ' . $exception->getMessage();
    die();
}



// Create app
$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response) use ($view) {
    $response->getBody()->write('Home Page');
    return $response;
});
$app->get('/login', function (Request $request, Response $response) use ($view) {
    $response->getBody()->write('Login Page');
    return $response;
});
$app->post('/login-post', function (Request $request, Response $response) {
    $response->getBody()->write('Login Page');
    return $response;
});
$app->get('/register', function (Request $request, Response $response) use ($view) {
    $response->getBody()->write('Login Page');
    return $response;
});
$app->get('/logout', function (Request $request, Response $response) {

    return $response;
});

$app->run();