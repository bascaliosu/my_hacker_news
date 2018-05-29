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

    /**
     * @return string
     */
    public function getTopStoriesUrl()
    {
        return self::HACKER_NEWS_API_URL . self::TOP_STORIES;
    }

    /**
     * @param $url
     *
     * @return bool|string
     */
    public function getStories($url)
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