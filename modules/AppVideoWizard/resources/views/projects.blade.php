@extends('layouts.app')

@section('title', __('My Video Projects'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">{{ __('My Video Projects') }}</h1>
        <a href="{{ route('app.video-wizard.index') }}" class="btn btn-primary">
            <i class="fa-light fa-plus mr-2"></i>
            {{ __('New Project') }}
        </a>
    </div>

    @if($projects->isEmpty())
        <div class="card bg-base-200">
            <div class="card-body text-center py-12">
                <i class="fa-light fa-video text-5xl text-base-content/30 mb-4"></i>
                <h3 class="text-lg font-semibold mb-2">{{ __('No projects yet') }}</h3>
                <p class="text-base-content/60 mb-4">{{ __('Create your first AI-powered video') }}</p>
                <a href="{{ route('app.video-wizard.index') }}" class="btn btn-primary">
                    {{ __('Create Video') }}
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($projects as $project)
                <div class="card bg-base-200 hover:bg-base-300 transition-colors">
                    <div class="card-body">
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="card-title text-lg">{{ $project->name }}</h2>
                                <p class="text-sm text-base-content/60">
                                    {{ $project->platform ? config("appvideowizard.platforms.{$project->platform}.name") : __('No platform') }}
                                </p>
                            </div>
                            <div class="badge {{ $project->status === 'completed' ? 'badge-success' : ($project->status === 'processing' ? 'badge-warning' : 'badge-ghost') }}">
                                {{ ucfirst($project->status) }}
                            </div>
                        </div>

                        <div class="flex items-center gap-4 text-sm text-base-content/60 mt-2">
                            <span>
                                <i class="fa-light fa-clock mr-1"></i>
                                {{ $project->target_duration }}s
                            </span>
                            <span>
                                <i class="fa-light fa-film mr-1"></i>
                                {{ count($project->script['scenes'] ?? []) }} scenes
                            </span>
                        </div>

                        <div class="text-xs text-base-content/40 mt-2">
                            {{ __('Updated') }} {{ $project->updated_at->diffForHumans() }}
                        </div>

                        <div class="card-actions justify-end mt-4">
                            <a href="{{ route('app.video-wizard.edit', $project->id) }}" class="btn btn-sm btn-primary">
                                {{ __('Continue') }}
                            </a>
                            <form action="{{ route('app.video-wizard.destroy', $project->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('{{ __('Are you sure you want to delete this project?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost text-error">
                                    <i class="fa-light fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $projects->links() }}
        </div>
    @endif
</div>
@endsection
