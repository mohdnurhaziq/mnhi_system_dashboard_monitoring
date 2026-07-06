<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Llm\LlmAnalysisService;
use Illuminate\Http\RedirectResponse;

class AnalysisController extends Controller
{
    public function analyze(Project $project, LlmAnalysisService $service): RedirectResponse
    {
        $result = $service->analyze($project);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
