<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearchService $search)
    {
        return response()->json([
            'results' => $search->search((string) $request->query('q', '')),
        ]);
    }
}
