<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessGraphExtraction;
use App\Models\Graph;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GraphController extends Controller
{
    public function index(Request $request)
    {
        $graphs = $request->user()
            ->graphs()
            ->withCount(['entities', 'relationships'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($graphs);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'website_url' => 'required|url|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $graph = Graph::create([
            'user_id' => $request->user()->id,
            'website_url' => $request->website_url,
            'status' => 'pending',
        ]);

        // Dispatch job to process extraction
        ProcessGraphExtraction::dispatch($graph);

        return response()->json($graph, 201);
    }

    public function show(Graph $graph)
    {
        // Ensure user owns this graph
        if ($graph->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $graph->load([
            'entities' => function ($query) {
                $query->orderBy('type')->orderBy('name');
            },
            'relationships.sourceEntity',
            'relationships.targetEntity',
            'sourcePages',
        ]);

        return response()->json($graph);
    }

    public function update(Request $request, Graph $graph)
    {
        // Ensure user owns this graph
        if ($graph->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'website_url' => 'sometimes|url|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $graph->update($request->only(['website_url']));

        return response()->json($graph);
    }

    public function destroy(Graph $graph)
    {
        // Ensure user owns this graph
        if ($graph->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $graph->delete();

        return response()->json(['message' => 'Graph deleted successfully']);
    }

    public function extract(Graph $graph)
    {
        // Ensure user owns this graph
        if ($graph->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Reset graph status and clear existing data
        $graph->update(['status' => 'pending']);
        $graph->entities()->delete();
        $graph->relationships()->delete();

        // Dispatch job to process extraction
        ProcessGraphExtraction::dispatch($graph);

        return response()->json([
            'message' => 'Extraction started',
            'graph' => $graph->fresh(),
        ]);
    }

    public function entities(Graph $graph)
    {
        // Ensure user owns this graph
        if ($graph->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $entities = $graph->entities()
            ->with(['sourcePages'])
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return response()->json($entities);
    }

    public function relationships(Graph $graph)
    {
        // Ensure user owns this graph
        if ($graph->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $relationships = $graph->relationships()
            ->with(['sourceEntity', 'targetEntity'])
            ->get();

        return response()->json($relationships);
    }
}
