<?php
/**
 * Class Helper
 *
 * @package Service
 *
 * @author  Bogdan Dinu <bogdan.dinu@innobyte.com>
 */

namespace Service;

class Helper
{
    /**
     * Variable used to store all comments from recursive method
     *
     * @var array
     */
    protected $allComments = [];

    /**
     * TTL for cache stories = 10 min
     *
     * @var int
     */
    protected $ttl =  10 * 60;

    /**
     * Used to retrieve host from an URL provided.
     *
     * @param $url
     *
     * @return mixed
     */
    public function extractDomain($url)
    {
        $urlComponents = parse_url($url);

        if (!empty($urlComponents['host'])) {
            return $urlComponents['host'];
        }

        return "";

    }

    /**
     * Used to cache series of storyIds for homepage and newest and retrieve all infos about stories
     *
     * @param        $request
     * @param        $app
     * @param        $storiesUrl
     * @param string $cacheKey
     *
     * @return array
     */
    public function getStories(
        $request,
        $app,
        $storiesUrl,
        $cacheKey = 'top_stories'
    ) {
        $currentPage = $request->query->get('page');
        if (is_null($currentPage)) {
            $currentPage = 1;
        }

        if ($currentPage < 1) {
            $currentPage = 1;
        }

        $offset = ($currentPage - 1) * $app['length'];
        $stories = [];

        /** @var HackerNewsApiCalls $hackerNewsService */
        $hackerNewsService = $app['hacker_news_calls'];

        if (!empty($app['cache']->fetch($cacheKey))) {
            $topStories = $app['cache']->fetch($cacheKey);
        } else {
            $topStories = $hackerNewsService->getStory($storiesUrl);
            $app['cache']->store($cacheKey, $topStories, $this->ttl);
        }

        $allTopStoriesIds = json_decode($topStories, true);

        $topStoriesIds = array_slice($allTopStoriesIds, $offset, $app['length']);

        foreach($topStoriesIds as $topStoriesId) {
            $stories[] = $this->getStoryById(
                $app,
                $topStoriesId
            );
        }

        return [
            'stories'       => $stories,
            'currentPage'   => $currentPage,
            'storiesCount'  => count($allTopStoriesIds)
        ];
    }

    /**
     * Used to retrieve a story by its id and cache the result
     *
     * @param $app
     * @param $storyId
     *
     * @return array
     */
    public function getStoryById(
        $app,
        $storyId
    ) {
        /** @var HackerNewsApiCalls $hackerNewsService */
        $hackerNewsService = $app['hacker_news_calls'];
        $cacheKey = "story_" . $storyId;
        if (!empty($app['cache']->fetch($cacheKey))) {
            $story = $app['cache']->fetch($cacheKey);
            $status = "cached";
        } else {
            $storyUrl = $hackerNewsService->getSingleStoryUrl($storyId);
            $story = $hackerNewsService->getStory($storyUrl);
            $app['cache']->store($cacheKey, $story, $this->ttl);
            $status = "fresh";
        }

        $newStory = json_decode($story, true);
        if (count($newStory)) {
            $newStory['status'] = $status;
            $newStory['host'] = $this->extractDomain($newStory['url']);
        }

        return $newStory;
    }

    /**
     * Recursive method used to retrieve nested comments for a story
     * If is not specified to run recursively, will return only first level of comments - used for user page
     *
     * @param      $app
     * @param      $itemIds
     * @param bool $recursive
     *
     * @return array
     */
    public function getCommentsByIds(
        $app,
        $itemIds,
        $recursive = true
    ) {
        foreach ($itemIds as $itemId) {
            $comment = $this->getStoryById($app, $itemId);
            if (!is_null($comment)) {
                $this->allComments[] = $comment;
            }

            if ($recursive) {
                if (isset($comment['kids']) && is_array($comment['kids'])) {
                    $this->getCommentsByIds($app, $comment['kids'], $recursive);
                }
            }
        }

        return $this->allComments;
    }
}