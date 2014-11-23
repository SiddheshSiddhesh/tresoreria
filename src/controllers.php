<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

// errors
$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html',
        'errors/'.substr($code, 0, 2).'x.html',
        'errors/'.substr($code, 0, 1).'xx.html',
        'errors/default.html',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});

// homepage
$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html', array());
})->bind('homepage');

// donors page
$app->get('/donors', function () use ($app) {
    $sql = "SELECT paymentID, amount/100 AS money, firstname, lastname FROM blog_fullstripe_payments";
    $single_donors = $app['db']->fetchAssoc($sql);

    $sql = "SELECT * FROM blog_fullstripe_subscribers";
    $subscription_donors = $app['db']->fetchAssoc($sql);

    $vars = array(
      'single_donors' => $single_donors,
      'subscription_donors' => $subscription_donors,
    );
    return $app['twig']->render('donors.html', $vars);
});

// results page
$app->get('/results', function () use ($app) {
    $sql = "SELECT SUM(amount)/100 AS money FROM blog_fullstripe_payments";
    $payments_money = $app['db']->fetchAssoc($sql);

    $sql = "SELECT planID AS plan, 
                   COUNT(*) AS number,
                   COUNT(*) * (
                     CASE planID 
                       WHEN 'monthly5' THEN 5 
                       WHEN 'monthly10' THEN 10 
                       WHEN 'monthly20' THEN 20 
                       WHEN 'monthly30' THEN 30 
                       WHEN 'monthly50' THEN 50 
                     END
                   ) AS money
            FROM blog_fullstripe_subscribers 
            WHERE planID <> '' GROUP BY planID";
    $subscriptions = $app['db']->fetchAll($sql);

    $sql = "SELECT COUNT(*) AS number FROM blog_fullstripe_subscribers WHERE planID <> ''";
    $subscriptions_number = $app['db']->fetchAssoc($sql);

    $sql = "SELECT SUM(
              CASE planID 
                WHEN 'monthly5' THEN 5 
                WHEN 'monthly10' THEN 10 
                WHEN 'monthly20' THEN 20 
                WHEN 'monthly30' THEN 30 
                WHEN 'monthly50' THEN 50 
              END
            ) AS money FROM blog_fullstripe_subscribers";
    $subscriptions_money = $app['db']->fetchAssoc($sql);

    $vars = array(
      'payments_money' => intval($payments_money['money']),
      'subscriptions' => $subscriptions,
      'subscriptions_number' => $subscriptions_number['number'],
      'subscriptions_money' => $subscriptions_money['money'],
    );
    return $app['twig']->render('results.html', $vars);
});
