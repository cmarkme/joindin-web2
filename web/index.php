<?php
require_once '../app/src/Application/Autoloader.php';

spl_autoload_register('Application\Autoloader::autoload');

session_cache_limiter(false);
session_start();

// include dependencies
require '../vendor/Slim/Slim.php';
require '../vendor/TwigView.php';
require '../vendor/predis-0.8/autoload.php';

// include view controller
require '../app/src/View/Filters.php';
require '../app/src/View/Functions.php';

$config = array();
$configFile = realpath(__DIR__ . '/../config/config.php');
if (is_readable($configFile)) {
    include $configFile;
} else {
    include realpath(__DIR__ . '/../config/config.php.dist');
}

// initialize Slim
$app = new Slim(
    array_merge(
        $config['slim'],
        array(
            'view' => new \TwigView(),
        )
    )
);

$app->configureMode('development', function() use ($app) {
    error_reporting(-1);
    ini_set('display_errors', 1);
    ini_set('html_errors', 1);
    ini_set('display_startup_errors', 1);
});

// Pass the current mode to the template, so we can choose to show
// certain things only if the app is in live/development mode
$app->view()->appendData(
    array('slim_mode' => $config['slim']['mode'])
);

// Other variables needed by the main layout.html.twig template
$app->view()->appendData(
    array(
        'google_analytics_id' => $config['slim']['custom']['googleAnalyticsId'],
        'user' => (isset($_SESSION['user']) ? $_SESSION['user'] : false),
    )
);

// set Twig base folder, view folder and initialize Joindin filters
\TwigView::$twigDirectory = realpath(__DIR__ . '/../vendor/Twig/lib/Twig');
$app->view()->setTemplatesDirectory('../app/templates');
View\Filters\initialize($app->view()->getEnvironment(), $app);
View\Functions\initialize($app->view()->getEnvironment(), $app);
$app->configureMode('development', function() use ($app) {
    $env = $app->view()->getEnvironment();
    $env->enableDebug();
    $env->addExtension(new \Twig_Extension_Debug());
});


// register routes
new Application\ApplicationController($app);
new Event\EventController($app);
new Search\SearchController($app);
new User\UserController($app);
new Talk\TalkController($app);

// execute application
$app->run();
