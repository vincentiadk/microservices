<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Catalog;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    // READ all records (GET)
    public function index()
    {
        return response()->json(Catalog::paginate(15));
    }

    // CREATE a new record (POST)
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'author' => 'required|string|max:255',
                'isbn' => 'required|string|unique:catalogs,isbn',
                'subject' => 'nullable|string',
            ]);

            $catalog = Catalog::create($validatedData);
            //\Log::channel('catalog')->info('Catalog created successfully.');

            return response()->json([
                'message' => 'Catalog created successfully.',
                'data' => $catalog
            ], 201);

        } catch (ValidationException $e) {
            \Log::channel('catalog')->error('Validasi gagal:', $e->errors());

            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);

        } catch (QueryException $e) {
            \Log::channel('catalog')->error('Kesalahan query:', ['message' => $e->getMessage()]);

            return response()->json([
                'message' => 'Kesalahan database.',
                'error' => $e->getMessage()
            ], 500);

        } catch (\Exception $e) {
            \Log::channel('catalog')->error('Kesalahan umum:', ['message' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // READ a single record (GET)
    public function show(Catalog $catalog)
    {
        $useCache = config('cache.use_cache');
        if ($useCache) {
            $cacheKey = "catalog:{$catalog->id}";
            if(Cache::has($cacheKey)) {
                \Log::info("Catalog {$catalog->id} served from cache.");
            } else {
                \Log::info("Catalog {$catalog->id} fetched from DB.");
            }
            $data = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($catalog) {
                return $catalog;
            });
        } else {
            $data = $catalog;
        }
        return response()->json($catalog);
    }

    // UPDATE a record (PUT/PATCH)
    public function update(Request $request, $id)
    {
        $catalog = Catalog::find($id);
        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'author' => 'sometimes|required|string|max:255',
            'isbn' => 'sometimes|required|string|unique:catalogs,isbn,' . $catalog->id,
            'published_year' => 'sometimes|required|digits:4',
            'subject' => 'nullable|string',
        ]);

        $catalog->update($validatedData);
        return response()->json($catalog);
    }

    // DELETE a record (DELETE)
    public function destroy($id)
    {
        \Log::info($id);
        $catalog = Catalog::find($id);
        $catalog->delete();
        return response()->json("delete catalog ID:$id success", 200); // 204 No Content
    }
}