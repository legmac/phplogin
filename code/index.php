<?php
use App\Database;
use App\Auth;
use App\AuthEx;
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

$database = new Database($dsn, $username, $password);
$auth = new Auth($database);
//var_dump($auth);

// Create app
$app = AppFactory::create();
$app->addBodyParsingMiddleware(); // $_POST


$app->get('/', function (Request $request, Response $response) use ($view) {
    $body = $view->render('index.html');
    $response->getBody()->write($body);
    return $response;
});
$app->get('/login', function (Request $request, Response $response) use ($view) {
    $body = $view->render('login.html');
    $response->getBody()->write($body);
    return $response;
});
$app->post('/login-post', function (Request $request, Response $response) {
    $response->getBody()->write('Login Page');
    return $response;
});
$app->get('/register', function (Request $request, Response $response) use ($view) {
    $body = $view->render('register.html');
    $response->getBody()->write($body);
    return $response;
});
$app->post('/register-post', function (Request $request, Response $response) use ($auth) {
    $params = (array) $request->getParsedBody();
    //var_dump($params);
    try{
    $auth->regstarion($params);
    }catch (AuthEx $e) {
        return $response->withHeader('Location', '/register')
        ->withStatus(302);
    }
    return $response->withHeader('Location', '/')
    ->withStatus(302);
});
$app->get('/logout', function (Request $request, Response $response) {
    return $response;
});

$app->run();