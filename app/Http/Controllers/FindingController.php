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
            'status' => ['required', 'in:open,dismissed,resolved'],
        ]);

        $finding->status = $validated['status'];
        // resolved_at tracks only the 'resolved' state; clear it otherwise.
        $finding->resolved_at = $validated['status'] === 'resolved'
            ? ($finding->resolved_at ?? now())
            : null;
        $finding->save();

        return back()->with('success', 'Finding updated.');
    }
}
