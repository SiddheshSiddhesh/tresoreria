<?php

use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;

// secure data
require_once __DIR__.'/../config/data.php';

// RSA Encryption
require_once __DIR__.'/../lib/Math/BigInteger.php';
require_once __DIR__.'/../lib/Crypt/RSA.php';

$app = new Application();
$app['debug'] = true; // FIX: this should be in config/dev.php, but there is too late

// db
$app->register(new DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_mysql',
        'host' => DB_HOST,
        'dbname' => DB_NAME,
        'user' => DB_USER,
        'password' => DB_PASSWORD,
        'charset' => 'utf8',
    ),
));

// auth
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new SecurityServiceProvider(), array(
    'security.firewalls' => array(
      'secured' => array(
        'pattern' => '^/admin/',
        'form' => array(
          'login_path' => '/',
          'check_path' => '/admin/login',
          'default_target_path' => '/admin/donors',
        ),
        'logout' => array('logout_path' => '/admin/logout'),
        'users' => array(
            'admin' => array('ROLE_ADMIN', 'qlFDImsuG6ToRzXyney95p6hs+dAf83oOVbamO2KQXzgiyjEUZ88aypNFMqJ5hmLW1kuxSQRqSYlc4m/6cAarw=='),
        ),
      ),
      'unsecured' => array(
        'anonymous' => true,
      ),
    ),
));
$app->boot();


// translation
$app->register(new TranslationServiceProvider(), array(
    'locale' => 'ca',
    'locale_fallback' => 'ca', // not working
));
$app['translator.domains'] = array(
    'messages' => array(
        'en' => array(
            'site_title' => "Treasure || Guanyem",
            'menu_home' => "Home",
            'menu_results' => "Results",
        ),
        'ca' => array(
            'site_title' => "Tresor || Guanyem",
            'menu_home' => "Portada",
            'menu_results' => "Resultats",
        ),
    ),
);
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());
$app['twig'] = $app->share($app->extend('twig', function ($twig, $app) {
    // add custom globals, filters, tags, ...

    return $twig;
}));

return $app;
