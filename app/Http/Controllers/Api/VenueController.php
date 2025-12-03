<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function index()
    {

        return response()->json(['data' => Venue::all()]);

        $venues = Venue::active()->get();
        
        return response()->json([
            'success' => true,
            'data' => $venues
        ]);
    }

    public function show(Venue $venue)
    {
        return response()->json([
            'success' => true,
            'data' => $venue
        ]);
    }
}
