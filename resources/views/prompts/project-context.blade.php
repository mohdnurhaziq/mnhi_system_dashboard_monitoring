@php
    $git = $metrics['git'] ?? [];
    $files = $metrics['files'] ?? [];
@endphp
# Project: {{ $project->name }}

**Path:** {{ $path }}
**Stack:** {{ $metrics['stack'] ?? 'unknown' }}@if(!empty($metrics['stack_version'])) ({{ $metrics['stack_version'] }})@endif

@if(!empty($git['has_commits']))
**Git:** {{ $git['commit_count'] ?? 0 }} commits, last on {{ $git['last_commit_at'] ?? 'unknown' }}, branch `{{ $git['current_branch'] ?? 'n/a' }}`@if(!empty($git['uncommitted_files'])), {{ $git['uncommitted_files'] }} uncommitted file(s)@endif
@elseif(!empty($git['has_git']))
**Git:** repository initialised but has **zero commits**.
@else
**Git:** not under version control.
@endif

## Detected gaps
@forelse($findings as $f)
- **[{{ strtoupper($f->severity) }}]** {{ $f->message }}
@empty
- None detected — project looks healthy on the tracked heuristics.
@endforelse

## README
@if($readme)
```
{{ $readme }}
```
@else
_No README found in this project._
@endif

## Recent commits
@if($gitLog)
```
{{ $gitLog }}
```
@else
_No commit history available._
@endif

## Project structure
@if(!empty($fileTree))
```
{{ $fileTree }}
```
@else
_Could not read project structure._
@endif

@if(!empty($keyFiles))
## Key files
@foreach($keyFiles as $kf)
### {{ $kf['path'] }}
```
{{ $kf['content'] }}
```
@endforeach
@endif

## Your task
Review this project and help me address the gaps above. Prioritise by severity
(critical first). For each item, explain what to change and why, then implement
it. If the project's purpose is unclear from the README and code, ask me before
making assumptions.
