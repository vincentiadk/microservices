<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SearchService;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{   
    protected $solrEndpoint;

    /**
     * SearchController constructor.
     */
    public function __construct()
    {
        // Mengambil URL endpoint Solr dari file konfigurasi
        $this->solrEndpoint = config('services.solr.endpoint');
    }

    public function search(Request $request, SearchService $searchService)
    {
        $query = strtolower($request->input('q', ''));
        $page = $request->input('page', 1);
        $searchField = $request->input('field', 'all');
        $perPage = 10;
        
        $useCache = config('cache.use_cache'); // dari config/cache.php
        $cacheKey = 'search:' . md5("q={$query}&page={$page}&field={$searchField}");

        if ($useCache) {
            if(Cache::has($cacheKey)) {
                \Log::info("Searching served from cache.");
            } else {
                \Log::info("Searching fetched from SOLR.");
            }
            $results = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($searchService, $query, $perPage, $page, $searchField) {
                return $searchService->search($query, $perPage, $page, $searchField);
            });
        } else {
            $results = $searchService->search($query, $perPage, $page, $searchField);
        }

        return $results;
    }

    public function cacheOrNot($params)
    {
        $solrUrl = config('services.solr.endpoint'); // pastikan ini ada di config/services.php
        
        $start = microtime(true);
        if(config('app.use_cache') == true){
            $response = cacheSolr($params, 'search');
            } else {
            $response = Http::get($solrUrl, $params);
        }
        $duration = microtime(true) - $start;
        \Log::info("Solr query time: {$duration}s", $params);
       
        return $response;
    }

    private function solrQuery($query, $qf, $mm = '100%')
    {
        $solrEndpoint = config('services.solr.endpoint');
        $params = [
            'q' => $query,
            'defType' => 'edismax',
            'qf' => $qf,
            'q.op' => 'AND',
            'mm' => $mm,
            'rows' => 50,
            'wt' => 'json'
        ];

        $response = Http::get($solrEndpoint, $params);

        if ($response->successful()) {
            $json = $response->json();
            return [
                'numFound' => $json['response']['numFound'],
                'docs' => $json['response']['docs']
            ];
        }

        return [
            'numFound' => 0,
            'docs' => []
        ];
    }


}
