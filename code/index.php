<?php
use App\Database;
use App\Auth;
use App\AuthEx;
use App\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
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
$auth = new Auth($connection);

// Create app
$app = AppFactory::create();
$app->addBodyParsingMiddleware(); // $_POST

//Sesion
$session = new Session();
$sessionMid = function(Request $request, Handler $handler) use ($session){
    $session->start();
    $response = $handler->handle($request);
    $session->save();
    return $response;
};
$app->add($sessionMid);

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
$app->get('/register', function (Request $request, Response $response) use ($view, $session) {
    $body = $view->render('register.html',[
        'msg'=> $session->flush('msg')
    ]);
    $response->getBody()->write($body);
    return $response;
});
$app->post('/register-post', function (Request $request, Response $response) use ($auth, $session) {
    $params = (array) $request->getParsedBody();
    //var_dump($params);
    try{
    $auth->regstarion($params);
    }catch (AuthEx $e) {
        $session->setData('msg', $e->getMessage());
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