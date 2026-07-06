@php
    $git = $metrics['git'] ?? [];
    $f = $scopedFinding;
@endphp
# Task for project: {{ $project->name }}

**Path:** {{ $path }}
**Stack:** {{ $metrics['stack'] ?? 'unknown' }}@if(!empty($metrics['stack_version'])) ({{ $metrics['stack_version'] }})@endif

## Gap to address
**[{{ strtoupper($f->severity) }}]** {{ $f->message }}

@if(!empty($f->details))
### Details
```
{{ json_encode($f->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
```
@endif

## Project context
@if(!empty($git['has_commits']))
- Git: {{ $git['commit_count'] ?? 0 }} commits, last on {{ $git['last_commit_at'] ?? 'unknown' }}
@elseif(!empty($git['has_git']))
- Git: initialised but zero commits
@else
- Git: not under version control
@endif

## README
@if($readme)
```
{{ $readme }}
```
@else
_No README found._
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
Resolve the gap described above for this project. Explain the change, then
implement it. Keep changes minimal and consistent with the project's existing
conventions.
