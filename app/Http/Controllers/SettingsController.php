<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingsController extends Controller
{
       // ------------------- EDIT LOW STOCK -------------------
    public function editLowStock()
    {
        try {
            $threshold = (int) setting('low_stock_threshold', 10);
            return view('settings.low_stock', compact('threshold'));

        } catch (\Throwable $e) {
            Log::error('Failed to load low stock settings', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            abort(500, 'Unable to load low stock settings');
        }
    }

    // ------------------- UPDATE LOW STOCK -------------------
    public function updateLowStock(Request $request)
    {
        $request->validate([
            'low_stock_threshold' => 'required|integer|min:1',
        ]);

        try {
            Setting::updateOrCreate(
                ['key' => 'low_stock_threshold'],
                ['value' => $request->low_stock_threshold]
            );

            return redirect()->back()->with('success', 'Low stock threshold updated!');

        } catch (\Throwable $e) {
            Log::error('Failed to update low stock threshold', [
                'user_id' => auth()->id(),
                'input' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update low stock threshold. Please try again.');
        }
    }
}
