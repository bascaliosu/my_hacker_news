<?php

use Silex\Application;
use Service\HackerNewsApiCalls;
use Service\Helper;

$app = new Application();



$app->register(new Silex\Provider\RoutingServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../templates/views'
));

$app->register(new Silex\Provider\AssetServiceProvider(), array(
    'assets.version' => 'v1',
    'assets.version_format' => '%s?version=%s',
    'assets.named_packages' => array(
        'css' => array('version' => 'css2', 'base_path' => __DIR__.'/css'),
        'images' => array('base_urls' => array('https://img.example.com')),
    ),
));

/**
 * Cache system uses apcu - can be changed accordingly to documentation
 * https://github.com/moust/silex-cache-service-provider
 */
$app->register(new Moust\Silex\Provider\CacheServiceProvider(), array(
    'cache.options' => array(
        'driver' => 'apcu'
    )
));

/**
 * Load HackerNewsApiCalls Service
 */
$app['hacker_news_calls'] = function () {
    return new HackerNewsApiCalls();
};

/**
 * Load Helper Service
 */
$app['helper'] = function () {
    return new Helper();
};

/**
 * Number of items per page
 */
$app['length'] = 10;

/**
 * Definition of homepage route
 */
$app->get('/', function (\Symfony\Component\HttpFoundation\Request $request) use ($app) {

    $topStoriesUrl = $app['hacker_news_calls']->getTopStoriesUrl();

    $topStoriesDetails = $app['helper']->getStories(
        $request,
        $app,
        $topStoriesUrl,
        'top_stories'
    );
    $stories = $topStoriesDetails['stories'];
    $currentPage = $topStoriesDetails['currentPage'];
    $storiesCount = $topStoriesDetails['storiesCount'];

    return $app['twig']->render('news.html.twig', array(
        'stories'       => $stories,
        'currentPage'   => $currentPage,
        'totalPages'    => ceil($storiesCount/$app['length']),
        'currentUrl'    => $app['url_generator']->generate('homepage')
    ));
})
    ->bind('homepage');

/**
 * Definition of newest route
 */
$app->get('/newest/', function (\Symfony\Component\HttpFoundation\Request $request) use ($app) {

    $newestStoriesUrl = $app['hacker_news_calls']->getNewestStoriesUrl();

    $newestStoriesDetails = $app['helper']->getStories(
        $request,
        $app,
        $newestStoriesUrl,
        'newest_stories'
    );
    $stories = $newestStoriesDetails['stories'];
    $currentPage = $newestStoriesDetails['currentPage'];
    $storiesCount = $newestStoriesDetails['storiesCount'];

    return $app['twig']->render('news.html.twig', array(
        'stories'       => $stories,
        'currentPage'   => $currentPage,
        'totalPages'    => ceil($storiesCount/$app['length']),
        'currentUrl'    => $app['url_generator']->generate('newest')
    ));
})
    ->bind('newest');

/**
 * Definition of comments route
 */
$app->get('/comments/{itemId}', function ($itemId) use ($app) {

    $story = $app['helper']->getStoryById($app, $itemId);
    $comments = [];

    if (isset($story['kids']) && is_array($story['kids'])) {
        $comments = $app['helper']->getCommentsByIds($app, $story['kids'], true);
    }

    return $app['twig']->render('comments.html.twig', array(
        'story'     => $story,
        'comments'  => $comments,
        'currentPage'   => 1,
        'totalPages'    => 1,
        'currentUrl'    => $app['url_generator']->generate('comments', ['itemId' => $itemId])
    ));
})
    ->bind('comments');

/**
 * Definition of user route
 */
$app->get('/user/{user}', function (\Symfony\Component\HttpFoundation\Request $request, $user) use ($app) {

    $currentPage = $request->query->get('page');
    if (is_null($currentPage)) {
        $currentPage = 1;
    }

    if ($currentPage < 1) {
        $currentPage = 1;
    }

    $offset = ($currentPage - 1) * $app['length'];

    /** @var HackerNewsApiCalls $hackerNewsService */
    $hackerNewsService = $app['hacker_news_calls'];

    $userUrl = $hackerNewsService->getUserUrl($user);

    $userDetails = $hackerNewsService->getStory($userUrl);
    $userDetails = json_decode($userDetails, true);

    $allUserStories = [];
    $stories = [];

    if (isset($userDetails['submitted']) && is_array($userDetails['submitted'])) {
        $allUserStories = $userDetails['submitted'];
        $userStories = array_slice($allUserStories, $offset, $app['length']);
        $stories = $app['helper']->getCommentsByIds($app, $userStories, false);
    }

    return $app['twig']->render('user.html.twig', array(
        'stories'       => $stories,
        'currentPage'   => $currentPage,
        'totalPages'    => ceil(count($allUserStories)/$app['length']),
        'currentUrl'    => $app['url_generator']->generate('user', ['user' => $user])
    ));
})
    ->bind('user');

return $app;
