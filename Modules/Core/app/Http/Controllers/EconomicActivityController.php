<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\EconomicActivity;

class EconomicActivityController extends Controller
{
    /**
     * Search economic activities for the searchable select component.
     */
    public function search(Request $request)
    {
        $query = $request->query('query', '');

        if (strlen($query) < 2 && empty($request->query('all'))) {
            return response()->json([]);
        }

        $activities = EconomicActivity::query()
            ->when($query, function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                  ->orWhere('name', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'code', 'name']);

        return response()->json($activities);
    }
}
