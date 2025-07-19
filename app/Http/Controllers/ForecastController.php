<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ForecastController extends Controller
{
    public function index()
    {
        $products = ['Beans', 'Nadu Rice', 'Dhal']; // you can load from DB later
        return view('forecast.index', compact('products'));
    }

    public function getData(Request $request)
    {
        // Optional: steps can be passed from UI; default to 10
        $steps = $request->input('steps', 10);

        /*$response = Http::post('https://beans-forecast-api.onrender.com/predict', [
            'steps' => $steps
        ]);*/
        $response = Http::timeout(180) // allow up to 60 seconds
        ->withOptions(['verify' => false]) // optional: skip SSL for local
        ->post('https://beans-forecast-api.onrender.com/predict', [
            'steps' => 10
        ]);



        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Prediction failed.'], 500);
    }
}
