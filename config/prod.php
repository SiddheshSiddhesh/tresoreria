<?php
// configure your app for the production environment

// RSA Encryption
require_once __DIR__.'/../lib/Math/BigInteger.php';
require_once __DIR__.'/../lib/Crypt/RSA.php';
require_once __DIR__.'/../config/secure.php';

$app['twig.path'] = array(__DIR__.'/../templates');
$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');
