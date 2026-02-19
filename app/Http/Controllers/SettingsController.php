<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function editLowStock()
    {
        $threshold = (int) setting('low_stock_threshold', 10);
        return view('settings.low_stock', compact('threshold'));
    }

    public function updateLowStock(Request $request)
    {
        $request->validate([
            'low_stock_threshold' => 'required|integer|min:1',
        ]);

        Setting::updateOrCreate(
            ['key' => 'low_stock_threshold'],
            ['value' => $request->low_stock_threshold]
        );

        return redirect()->back()->with('success', 'Low stock threshold updated!');
    }
}
