<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;


class SearchService
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

    public function search(string $query,  int $perPage = 10, int $page = 1, $searchField = "all"):  array
    {
        // 1. Penerimaan query teks (Q) dari pengguna.
        $useCache = config('cache.use_cache');
        if (empty($query)) {
            // Kembalikan paginator kosong jika tidak ada query
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }
         // 2. Analisis query dengan menghitung jumlah kata (n).
        $wordCount = Str::wordCount($query);
        
        // Panggil method private dengan parameter paginasi
        if ($wordCount === 1) {
            $solrResult = $this->searchWithStandard($query, $perPage, $page, $searchField);
            if (count($solrResult['docs']) == 0) {
                $solrResult = $this->searchWithFuzzy($query, $perPage, $page, $searchField);
            }
        } else {
            $solrResult = $this->searchWithNgram($query, $perPage, $page, $searchField);
        }
        
        // 2. BUAT PAGINATOR SECARA MANUAL
        $paginator =  new LengthAwarePaginator(
            $solrResult['docs'],      // Item untuk halaman ini
            $solrResult['total'],     // Total semua item
            $perPage,                 // Item per halaman
            $page,                    // Halaman saat ini
            [
                'path' => request()->url(), // Opsi agar URL paginasi benar
                'query' => request()->query(),
            ]
        );
        return [
            'paginator' => $paginator,
            'facets'    => $solrResult['facets'],
        ];
       
    }

    /**
     * Konfigurasi pencarian "text_standart".
     *
     * @param string $term
     * @return Collection
     */
    private function searchWithStandard(string $term, int $perPage, int $page, $searchField)
    {
        // Query ke Solr untuk pencarian standar pada field 'text_standart'
        $solrQuery = $this->escapeSolrValue($term);
        $qf = 'standard';
        return $this->executeSolrQuery($solrQuery, $term, $qf, $perPage, $page, $searchField);
    }

    /**
     * Konfigurasi pencarian "fuzzy" sebagai fallback.
     *
     * @param string $term
     * @return Collection
     */
    private function searchWithFuzzy(string $term, int $perPage, int $page, $searchField)
    {
        // Menambahkan operator ~ untuk fuzzy search di Solr
        $solrQuery =  $this->escapeSolrValue($term) . "~2";
        $qf = "fuzzy";
        return $this->executeSolrQuery($solrQuery, $term, $qf, $perPage, $page, $searchField);
    }

    /**
     * Konfigurasi pencarian "text_ngram".
     *
     * @param string $phrase
     * @return Collection
     */
    private function searchWithNgram(string $phrase, int $perPage, int $page, $searchField)
    {
        // Menggunakan tanda kutip untuk pencarian frasa yang tepat pada field n-gram
        $solrQuery = $this->escapeSolrValue($phrase);
        $qf = "ngram";
        return $this->executeSolrQuery($solrQuery, $phrase, $qf, $perPage, $page, $searchField);
    }
    
    /**
     * Mengeksekusi query ke Solr dan memformat hasilnya.
     *
     * @param string $solrQuery
     * @return Collection
     */
    private function executeSolrQuery(string $solrQuery, $term, $qf, int $perPage, int $page, $searchField): array
    {
        // HITUNG OFFSET UNTUK SOLR
        $start = ($page - 1) * $perPage;
        try {
            $words = preg_split('/\s+/', trim($term));
            if (count($words) < 2) {
                $mm_value = '100%';
            } else {
                $mm_value = '75%';
            }
            //\Log::info($qf);
            switch($qf){
                case 'standard':
                    $params =  [
                        'q'  => $solrQuery,
                        'wt' => 'json', // Meminta response dalam format JSON
                        'defType' => 'edismax',
                        'mm' => $mm_value,
                        'qf' => $searchField == "all" ? "ts" : $searchField,
                        'rows' => $perPage,
                        'start' => $start,
                        'tie' => 0.1,
                    ];
                
                    break;
                case 'ngram':
                    $sf =  $searchField == "all" ? "ts_ngram" : $searchField . '_ngram';
                    if($searchField == 'title' || $searchField == 'author'){
                        $sf = $searchField. "_ngram";
                    }
                    //\Log::info($sf);
                    $params =  [
                        'q'  => $solrQuery,
                        'wt' => 'json', // Meminta response dalam format JSON
                        'defType' => 'edismax',
                        'mm' => $mm_value,
                        'qf' =>$sf,
                        'rows' => $perPage,
                        'start' => $start,
                        'tie' => 0.1,
                    ];
                    break;
                case'fuzzy':
                    $params  = [
                        'q'  => $searchField == "all" ? ("ts" . ':' .$solrQuery) : ($searchField. ':' . $solrQuery),
                        'wt' => 'json', // Meminta response dalam format JSON
                        'rows' => $perPage,
                        'start' => $start
                    ];
                    break; 

            }
            $params = array_merge($params, [
                'facet' => 'true', // Mengaktifkan faceting
                'facet.mincount' => 1, // Hanya tampilkan facet dengan jumlah > 0
                'facet.limit' => 5]
            );    // Batasi 5 hasil teratas per facet)

            $queryString = http_build_query($params);
            // 3. Definisikan field untuk facet secara terpisah
            $facetFields = ['klasbesar', 'author_s', 'subject_s', 'publishlocation_s', 'publisher_s', 'publishyear', 'worksheet_id']; // <-- Masukkan semua field facet di sini

            // 4. Tambahkan setiap field facet ke query string secara manual
            foreach ($facetFields as $field) {
                $queryString .= '&facet.field=' . urlencode($field);
            }
            
            // 5. Gabungkan endpoint dengan query string untuk membuat URL final
            $finalUrl = $this->solrEndpoint . '?' . $queryString;
            // 6. Lakukan request HANYA dengan URL final
            $response = Http::get($finalUrl);

            //if ($response->failed()) {
            //    return ['docs' => [], 'total' => 0, 'facets' => []];
            //}

            $data = $response->json();
            return [
                'docs' => collect($data['response']['docs'] ?? [])->map(fn($item) => (object) $item),
                'total' => $data['response']['numFound'] ?? 0,
                'facets' => $data['facet_counts']['facet_fields'] ?? [],
            ];

        } catch (\Exception $e) {
            return ['docs' => [], 'total' => 0, 'facets' => []];
        }
    }

    /**
     * Fungsi sederhana untuk escaping karakter spesial Solr.
     *
     * @param string $value
     * @return string
     */
    private function escapeSolrValue(string $value): string
    {
        $pattern = '/([\\+\\-\\!\\(\\)\\:\\^\\[\\]\\"\\{\\}\\~\\*\\?\\|\\&\\;\\\\])/';
        $replace = '\\\\$1';
        return preg_replace($pattern, $replace, $value);
    }
}
