<?php

use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;

$app = new Application();

// db
$app->register(new DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_mysql',
        'host' => 'localhost',
        'dbname' => 'tresor',
        'user' => 'tresor',
        'password' => 'FZk5bVeChkRc38j',
        'charset' => 'utf8',
    ),
));

// auth
$app->register(new SecurityServiceProvider(), array(
    'security.firewalls' => array(
      'main' => array(
          'pattern' => '^/',
          'http' => true,
          'users' => array(
              'admin' => array('ROLE_ADMIN', 'qlFDImsuG6ToRzXyney95p6hs+dAf83oOVbamO2KQXzgiyjEUZ88aypNFMqJ5hmLW1kuxSQRqSYlc4m/6cAarw=='),
          ),
          'logout' => array('logout_path' => '/logout'), // not working
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
