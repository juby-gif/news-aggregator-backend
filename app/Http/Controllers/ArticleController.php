<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ArticleController extends Controller
{
    private $apiClients = [];

    public function __construct()
    {
        $this->initializeApiClients();
    }

    /**
     * Initialize the API clients with their respective configurations.
     *
     * FYI: API Keys were added publicly for test purpose only
    */
    private function initializeApiClients()
    {
        $this->apiClients = [
            'newsapi' => [
                'base_uri' => 'https://newsapi.org/v2/everything',
                'api_key' => '8bb849fd47c3470a831e23cac8a10697',
                'api_key_param' => 'apiKey',
                'cache_key' => 'newsapi_articles',
                'cache_expiration' => 60, // Cache expiration in minutes
            ],
            'theguardian' => [
                'base_uri' => 'https://content.guardianapis.com/search',
                'api_key' => '46c38fc6-0fdf-42f7-b699-8bedf473b1eb',
                'api_key_param' => 'api-key',
                'cache_key' => 'theguardian_articles',
                'cache_expiration' => 60,
            ],
            'nytimes' => [
                'base_uri' => 'https://api.nytimes.com/svc/search/v2/articlesearch.json',
                'api_key' => 'KtDXVXRFO61TZVlxaGGNnmyUamJubQRi',
                'api_key_param' => 'api-key',
                'cache_key' => 'nytimes_articles',
                'cache_expiration' => 60,
            ],
            // Add other API clients for different services
        ];
    }

    /**
     * Fetch articles based on the provided keyword and filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
    */
    public function fetchArticles(Request $request)
    {
        $keyword = $request->input('keyword');
        $filters = $this->extractFilters($request);

        $articles = $this->getCachedArticles($keyword, $filters);

        return response()->json($articles);
    }

    /**
     * Extract filters (date, category, source) from the request parameters.
     *
     * @param Request $request
     * @return array
    */
    private function extractFilters(Request $request)
    {
        $filters = [];

        foreach (['date', 'category', 'source'] as $filter) {
            if ($request->has($filter)) {
                $filters[$filter] = $request->input($filter);
            }
        }

        return $filters;
    }

    /**
     * Get the articles from the cache or make an API request if not cached.
     *
     * @param string $keyword
     * @param array $filters
     * @return array
    */
    private function getCachedArticles(string $keyword, array $filters)
    {
        $articles = [];

        foreach ($this->apiClients as $service => $apiClient) {
            $cacheKey = $this->generateCacheKey($service, $keyword, $filters);
            $articles[$service] = Cache::remember($cacheKey, $apiClient['cache_expiration'], function () use ($apiClient, $keyword, $filters, $service) {
                $response = $this->makeApiRequest($apiClient, $keyword, $filters);
                return $this->processApiResponse($response, $service);
            });
        }

        return $articles;
    }

    /**
     * Generate a cache key for the given service, keyword, and filters.
     *
     * @param string $service
     * @param string $keyword
     * @param array $filters
     * @return string
     */
    private function generateCacheKey(string $service, string $keyword, array $filters)
    {
        $filterString = implode('_', $filters);
        return "{$service}_{$keyword}_{$filterString}";
    }

    /**
     * Make an API request to the specified service with the provided keyword and filters.
     *
     * @param array $apiClient
     * @param string $keyword
     * @param array $filters
     * @return array
    */
    private function makeApiRequest(array $apiClient, string $keyword, array $filters)
    {
        $queryParams = [
            'q' => $keyword,
        ];

        // Add filters to query parameters if provided
        foreach ($filters as $filter => $value) {
            $queryParams[$filter] = $value;
        }

        $queryParams[$apiClient['api_key_param']] = $apiClient['api_key'];

        $client = new Client();

        $response = $client->get($apiClient['base_uri'], [
            'query' => $queryParams,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Process the API response based on the service.
     *
     * @param array $response
     * @param string $service
     * @return array
    */
    private function processApiResponse(array $response, string $service)
    {
        // You can process and manipulate the articles data based on the service

        return $response;
    }
}
