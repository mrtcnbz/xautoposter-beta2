<?php
namespace XAutoPoster\Services;

class ApiService {
    private $nodeUrl;
    
    public function __construct() {
        $this->nodeUrl = defined('XAUTOPOSTER_NODE_URL') ? 
            XAUTOPOSTER_NODE_URL : 'http://localhost:3000/wordpress';
    }
    
    public function verifyTwitterCredentials($credentials) {
        try {
            $response = $this->post('/verify', $credentials);
            return $response && $response->success;
        } catch (\Exception $e) {
            error_log('Twitter API Verification Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function sharePost($post) {
        try {
            $postData = $this->formatPostData($post);
            $response = $this->post('/share', ['post' => $postData]);
            return $response && $response->success ? $response->data : null;
        } catch (\Exception $e) {
            error_log('Twitter Share Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function getMetrics($tweetId) {
        try {
            $response = $this->get("/metrics/{$tweetId}");
            return $response && $response->success ? $response->data : null;
        } catch (\Exception $e) {
            error_log('Twitter Metrics Error: ' . $e->getMessage());
            return null;
        }
    }
    
    private function formatPostData($post) {
        return [
            'title' => html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
            'link' => get_permalink($post),
            'categories' => wp_list_pluck(get_the_category($post->ID), 'name'),
            'featuredImage' => get_the_post_thumbnail_url($post->ID, 'full')
        ];
    }
    
    private function post($endpoint, $data) {
        $response = wp_remote_post($this->nodeUrl . $endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($data),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body);
    }
    
    private function get($endpoint) {
        $response = wp_remote_get($this->nodeUrl . $endpoint, [
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body);
    }
}