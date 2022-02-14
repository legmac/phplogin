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

\Sentry\init(['dsn' => 'https://9d03f29909e14d10aeceae31e80004e2@o1141372.ingest.sentry.io/6204391' ]);

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

$auth = new Auth($connection, $session);

$app->get('/', function (Request $request, Response $response) use ($view, $session) {
    $body = $view->render('index.html',[
        'user'=> $session->getData('user')
        ]);
    $response->getBody()->write($body);
    return $response;
});
$app->get('/login', function (Request $request, Response $response) use ($view, $session) {
    $body = $view->render('login.html',[
    'msg'=> $session->flush('msg'),
    'form'=> $session->flush('form')
    ]);
    $response->getBody()->write($body);
    return $response;
});
$app->post('/login-post', function (Request $request, Response $response) use ($auth, $session){
    //$response->getBody()->write('Login Page');
    $params = (array) $request->getParsedBody();
    try{
    $auth->login($params['email'],$params['password']);
    }catch(AuthEx $e){
        $session->setData('msg', $e->getMessage());
        $session->setData('form', $params);
        return $response->withHeader('Location', '/login')
        ->withStatus(302);
    }
    return $response->withHeader('Location', '/')
    ->withStatus(302);
});
$app->get('/register', function (Request $request, Response $response) use ($view, $session) {
    $body = $view->render('register.html',[
        'msg'=> $session->flush('msg'),
        'form'=> $session->flush('form')
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
        $session->setData('form', $params);
        return $response->withHeader('Location', '/register')
        ->withStatus(302);
    }
    return $response->withHeader('Location', '/')
    ->withStatus(302);
});
$app->get('/logout', function (Request $request, Response $response) use ($session){
    $session->setData('user', null);
    return $response->withHeader('Location', '/')
    ->withStatus(302);
});



$app->run();