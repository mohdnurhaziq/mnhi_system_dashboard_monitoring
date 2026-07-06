<?php

namespace App\Http\Controllers;

use App\Models\Finding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FindingController extends Controller
{
    public function update(Request $request, Finding $finding): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,dismissed'],
        ]);

        $finding->update($validated);

        return back()->with('success', 'Finding updated.');
    }
}
