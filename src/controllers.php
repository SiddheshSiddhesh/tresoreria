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
    $sql = "SELECT paymentID, FLOOR(amount/100) AS money, firstname, lastname, created FROM blog_fullstripe_payments WHERE eventID = ? ORDER BY created DESC";
    $single_donors = $app['db']->fetchAll($sql, array('BANK ACCOUNT PAYMENT'));

    $vars = array(
      'detail_uri' => '/donors/single/',
      'single_donors' => $single_donors,
    );
    return $app['twig']->render('donors_single.html', $vars);
});
$app->get('/donors/subscribers', function () use ($app) {
    $sql = "SELECT subscriberID, planID, firstname, lastname, created FROM blog_fullstripe_subscribers WHERE stripeCustomerID = ? ORDER BY created DESC";
    $subscription_donors = $app['db']->fetchAll($sql, array('BANK ACCOUNT PAYMENT'));

    $vars = array(
      'detail_uri' => '/donors/subscriber/',
      'subscription_donors' => $subscription_donors,
    );
    return $app['twig']->render('donors_subscription.html', $vars);
});

// donor detail
$app->get('/donors/{type}/{id}', function ($type, $id) use ($app) {
    if ($type == 'single'){
      $sql = "SELECT FLOOR(amount/100) AS money, firstname, lastname, email, telephone, documentType, documentID, birthDate, addressCountry, addressLine1, addressCity, addressState, addressZip, created, bankCCC, bankIBAN, bankBIC 
              FROM blog_fullstripe_payments
              WHERE paymentID = ?";
    }else if ($type == 'subscriber'){
      $sql = "SELECT planID, firstname, lastname, email, telephone, documentType, documentID, birthDate, addressCountry, addressLine1, addressCity, addressState, addressZip, created, bankCCC, bankIBAN, bankBIC 
              FROM blog_fullstripe_subscribers
              WHERE subscriberID = ?";
    }
    $detail = $app['db']->fetchAssoc($sql, array((int) $id));

    // decrypt encrypted data
    $rsa = new Crypt_RSA();
    $private_key = file_get_contents(PRIVATE_KEY_PATH);
    $rsa->loadKey($private_key);
    foreach ($detail as $key => $value) {
      switch ($key){
        case 'bankCCC':
        case 'bankIBAN':
        case 'bankBIC':
          if ($value !== 'BANK FIELD NOT FILLED'){
            $detail[$key] = $rsa->decrypt($value);
          }
          break;
      }
    }

    $vars = array(
      'type' => $type,
      'detail' => $detail,
    );
    return $app['twig']->render('donors_detail.html', $vars);
});

// results page
$app->get('/results', function () use ($app) {
    $sql = "SELECT FLOOR(SUM(amount)/100) AS money FROM blog_fullstripe_payments";
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
      'payments_money' => $payments_money['money'],
      'subscriptions' => $subscriptions,
      'subscriptions_number' => $subscriptions_number['number'],
      'subscriptions_money' => $subscriptions_money['money'],
    );
    return $app['twig']->render('results.html', $vars);
});
