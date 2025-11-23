<?php

namespace App\Http\Controllers;

use App\Models\Graph;
use App\Models\Source;
use Illuminate\Http\Request;

class SourceController extends Controller
{
    /**
     * Display a paginated listing of sources for a graph
     */
    public function index(Request $request, Graph $graph)
    {
        // Ensure user owns this graph
        if ($graph->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:255',
            'sort_by' => 'sometimes|in:title,url,discovered_at',
            'sort_order' => 'sometimes|in:asc,desc',
        ]);

        $perPage = $request->input('per_page', 25);
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'discovered_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $query = Source::where('graph_id', $graph->id);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $sources = $query->paginate($perPage);

        return response()->json([
            'sources' => $sources->items(),
            'pagination' => [
                'current_page' => $sources->currentPage(),
                'per_page' => $sources->perPage(),
                'total' => $sources->total(),
                'last_page' => $sources->lastPage(),
                'from' => $sources->firstItem(),
                'to' => $sources->lastItem(),
            ],
        ]);
    }
}
