<?php

use Service\HackerNewsApiCalls;

ini_set('display_errors', 0);

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app['hacker_news_calls'] = function () {
    return new HackerNewsApiCalls();
};

$app->get('/', function () use ($app) {
    $service = $app['hacker_news_calls'];
    $topStoriesUrl = $service->getTopStoriesUrl();
    $topStories = $service->getStories($topStoriesUrl);
    $topStoriesArray = json_decode($topStories, true);

    $return = "";

    foreach($topStoriesArray as $ts) {
        $return .= $ts . "<br />";
    }

    return $return;
});

$app->get('/newest/', function () use ($app) {
    return "newest";
});

$app->run();