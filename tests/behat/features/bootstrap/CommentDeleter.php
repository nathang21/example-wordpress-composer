<?php

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

define('BASE_URL_FOR_WP_REST', "http://" . getenv('TERMINUS_ENV')."-".getenv('TERMINUS_SITE') . ".pantheonsite.io/");
define('BASIC_AUTH_OPTIONS', ['auth' => [getenv('ADMIN_USERNAME'), getenv('ADMIN_PASSWORD')]]);

/**
 * A quick and dirty class for deleting all comments with the WP REST API.
 * Much faster than doing the same over SSH with Terminus/WPCLI.
 */
class CommentDeleter {

    function get_comments() {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', BASE_URL_FOR_WP_REST . 'wp-json/wp/v2/comments?status=hold', BASIC_AUTH_OPTIONS);
        return '' . $response->getBody();
    }

    function get_comment_ids() {

        $comments = json_decode(get_comments());
        $return = [];
        foreach($comments as $comment) {
            $return[] = $comment->id;
        }

        return $return;
    }

    function delete_all_comments() {
        $comment_ids = get_comment_ids();

        $client = new Client();

        $requests = function ($ids) {
            foreach ($ids as $id) {
                yield new Request('DELETE', BASE_URL_FOR_WP_REST . "wp-json/wp/v2/comments/" . $id, ['Authorization' => "Basic " . base64_encode(BASIC_AUTH_OPTIONS['auth'][0] . ':' . BASIC_AUTH_OPTIONS['auth'][1]  )]);
            }
        };

        $pool = new Pool($client, $requests(get_comment_ids()), [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) {
                // this is delivered each successful response

//            print_r("\n\n");
//            print_r($response);
//
//            print_r("\n\n");
//
//            print_r($index);
            },
            'rejected' => function ($reason, $index) {

//            print_r("\n\n");
//            print_r($reason);
//
//            print_r("\n\n");
//
//            print_r($index);
//        // this is delivered each failed request
            },
        ]);

// Initiate the transfers and create a promise
        $promise = $pool->promise();

// Force the pool of requests to complete.
        $promise->wait();
    }

}



