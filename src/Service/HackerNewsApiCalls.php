<?php
/**
 * Class HackerNewsApiCalls
 *
 * @author Bogdan Dinu <bogdan.dinu@innobyte.com>
 */

namespace Service;

use GuzzleHttp\Client;

class HackerNewsApiCalls
{
    const HACKER_NEWS_API_URL   = "https://hacker-news.firebaseio.com/v0/";

    const TOP_STORIES           = "topstories.json";
    const NEW_STORIES           = "newstories.json";
    const USER_DETAILS          = "user/";

    /**
     * Generate url for top stories - homepage
     *
     * @return string
     */
    public function getTopStoriesUrl()
    {
        return self::HACKER_NEWS_API_URL . self::TOP_STORIES;
    }

    /**
     * Generate url for newest page
     *
     * @return string
     */
    public function getNewestStoriesUrl()
    {
        return self::HACKER_NEWS_API_URL . self::NEW_STORIES;
    }

    /**
     * Generate url for user page
     *
     * @param $user
     *
     * @return string
     */
    public function getUserUrl($user)
    {
        return self::HACKER_NEWS_API_URL . self::USER_DETAILS . $user . ".json";
    }

    /**
     * Generate url for a story based on Id
     *
     * @param $storyId
     *
     * @return string
     */
    public function getSingleStoryUrl($storyId)
    {
        return self::HACKER_NEWS_API_URL . "item/" . $storyId . ".json";
    }

    /**
     * Perform API Call to a specified URL and return the response from server
     *
     * @param $url
     *
     * @return bool|string
     */
    public function getStory($url)
    {
        try {
            $client = new Client();
            $response = $client->request(
                'GET',
                $url,
                []
            );
            if ($response->getStatusCode() != '200') {
                throw new \Exception('Error getting top stories results.'.(string)($response->getBody()));
            }
            $body = mb_convert_encoding($response->getBody(), 'UTF-8', 'UTF-8');
        } catch (\Exception $e) {
            return false;
        }
        return $body;
    }
}