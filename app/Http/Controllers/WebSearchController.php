<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SearchService;
use Illuminate\Support\Facades\Cache;

class WebSearchController extends Controller
{
    public function index(Request $request, SearchService $searchService)
    {
        $query = strtolower($request->input('q', ''));
        $page = $request->input('page', 1);
        $searchField = $request->input('field');
        $perPage = 10;
        $searchTime = 0; // Nilai default
        $useCache = config('cache.use_cache');
        $cacheKey = 'search:' . md5("q={$query}&page={$page}&field={$searchField}");

        if ($query) {
            // 1. Catat waktu mulai (dalam detik dengan presisi mikrodetik)
            $startTime = microtime(true);
            
            // 2. Eksekusi pencarian
            
            if ($useCache) {
                if(Cache::has($cacheKey)) {
                    \Log::info("Searching served from cache.");
                } else {
                    \Log::info("Searching fetched from SOLR.");
                }
                $searchData = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($searchService, $query, $perPage, $page, $searchField) {
                    return $searchService->search($query, $perPage, $page, $searchField);
                });
            } else {
                $searchData = $searchService->search($query, $perPage, $page, $searchField);
            }



            //$searchData = $searchService->search($query, $perPage, $page, $searchField);

            // 3. Catat waktu selesai
            $endTime = microtime(true);

            // 4. Hitung durasi dalam milidetik (ms)
            $searchTime = ($endTime - $startTime) * 1000;
        } else {
            // Jika tidak ada query, gunakan paginator kosong
            $searchData = [
               'paginator' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage),
               'facets' => []
            ];
        }

        $results = $searchData['paginator'];
        $facets = $searchData['facets'];

        // Kirim variabel yang sudah benar ke view
        return view('search_results', [
            'results'    => $results,
            'query'      => $query,
            'searchTime' => $searchTime,
            'facets'     => $facets,
            'searchField'=> $searchField,
        ]);
    }
}