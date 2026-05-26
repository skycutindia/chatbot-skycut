@extends('layouts.websites-workspace')

@section('title', 'Knowledge — '.$website->name)

@section('ws-tab', 'knowledge')
@section('ws-title', 'Knowledge base')
@section('ws-subtitle', 'Articles and documents that power bot responses.')

@section('page-toolbar')
<a href="{{ route('websites.knowledge.training', $website) }}" class="dash-btn-primary dash-btn-sm">Train &amp; import</a>
@endsection

@section('workspace')
    <form method="GET" action="{{ route('websites.knowledge.index', $website) }}" class="ws-filter-bar">
        <div class="dash-field flex-1 min-w-[200px]">
            <label class="dash-label" for="knowledge_search">Search articles</label>
            <input id="knowledge_search" type="search" name="q" value="{{ $search ?? '' }}" placeholder="Title or body…" class="dash-input w-full">
        </div>
        <button type="submit" class="dash-btn-secondary">Search</button>
        @if(!empty($search))
            <a href="{{ route('websites.knowledge.index', $website) }}" class="dash-btn-ghost">Clear</a>
        @endif
    </form>

    <div class="mt-8 grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-4">
            @foreach($articles as $article)
                <div class="dash-card">
                    <div class="dash-card-body">
                        <div class="cf-split">
                            <h3 class="font-semibold">{{ $article->title }}</h3>
                            <div class="cf-cluster">
                                <button type="button" class="dash-btn-ghost dash-btn-sm" data-article-edit-toggle="{{ $article->id }}">Edit</button>
                                <form method="POST" action="{{ route('websites.knowledge.articles.destroy', [$website, $article]) }}" onsubmit="return confirm('Delete this article?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dash-btn-danger dash-btn-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                        <p class="text-sm dash-muted mt-2" id="article-preview-{{ $article->id }}">{{ Str::limit(strip_tags($article->content), 200) }}</p>
                        <form
                            id="article-edit-{{ $article->id }}"
                            method="POST"
                            action="{{ route('websites.knowledge.articles.update', [$website, $article]) }}"
                            class="dash-article-edit-form hidden mt-4 space-y-3"
                        >
                            @csrf
                            @method('PUT')
                            <div class="dash-field">
                                <label class="dash-label" for="title-{{ $article->id }}">Title</label>
                                <input id="title-{{ $article->id }}" name="title" value="{{ $article->title }}" required class="dash-input w-full">
                            </div>
                            <div class="dash-field">
                                <label class="dash-label" for="content-{{ $article->id }}">Content</label>
                                <textarea id="content-{{ $article->id }}" name="content" rows="8" required class="dash-textarea w-full">{{ $article->content }}</textarea>
                            </div>
                            @if($categories->isNotEmpty())
                                <div class="dash-field">
                                    <label class="dash-label" for="cat-{{ $article->id }}">Category</label>
                                    <select id="cat-{{ $article->id }}" name="knowledge_category_id" class="dash-select w-full">
                                        <option value="">— None —</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" @selected($article->knowledge_category_id === $cat->id)>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_published" value="1" @checked($article->is_published)>
                                Published
                            </label>
                            <div class="cf-cluster">
                                <button type="submit" class="dash-btn-primary dash-btn-sm">Save changes</button>
                                <button type="button" class="dash-btn-ghost dash-btn-sm" data-article-edit-cancel="{{ $article->id }}">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
            {{ $articles->links() }}
        </div>

        <div class="space-y-6">
            <form method="POST" action="{{ route('websites.knowledge.articles.store', $website) }}" class="dash-card">
                <div class="dash-card-body space-y-3">
                    @csrf
                    <h3 class="dash-form-section-title">New article</h3>
                    <div class="dash-field">
                        <label class="dash-label" for="article_title">Title</label>
                        <input id="article_title" name="title" placeholder="Title" required class="dash-input w-full">
                    </div>
                    <div class="dash-field">
                        <label class="dash-label" for="article_content">Content</label>
                        <textarea id="article_content" name="content" placeholder="Content" rows="6" required class="dash-textarea w-full"></textarea>
                    </div>
                    <button type="submit" class="dash-btn-primary w-full">Publish</button>
                </div>
            </form>

            <form method="POST" action="{{ route('websites.knowledge.categories.store', $website) }}" class="dash-card">
                <div class="dash-card-body space-y-3">
                    @csrf
                    <h3 class="dash-form-section-title">New category</h3>
                    <div class="dash-field">
                        <label class="dash-label" for="category_name">Category name</label>
                        <input id="category_name" name="name" placeholder="Category name" required class="dash-input w-full">
                    </div>
                    <button type="submit" class="dash-btn-secondary w-full">Add</button>
                </div>
            </form>

            <form method="POST" action="{{ route('websites.knowledge.import', $website) }}" enctype="multipart/form-data" class="dash-card">
                <div class="dash-card-body space-y-3">
                    @csrf
                    <h3 class="dash-form-section-title">Bulk import (CSV)</h3>
                    <div class="dash-field">
                        <label class="dash-label" for="import_type">Import type</label>
                        <select id="import_type" name="import_type" class="dash-select w-full">
                            <option value="qa">Q&A pairs (question, answer)</option>
                            <option value="articles">Articles (title, content)</option>
                        </select>
                    </div>
                    <div class="dash-field">
                        <label class="dash-label" for="import_file">CSV file</label>
                        <input id="import_file" type="file" name="file" accept=".csv,.txt" required class="dash-input w-full">
                    </div>
                    <button type="submit" class="dash-btn-primary w-full">Import</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-article-edit-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-article-edit-toggle');
        document.getElementById('article-edit-' + id)?.classList.remove('hidden');
        document.getElementById('article-preview-' + id)?.classList.add('hidden');
    });
});
document.querySelectorAll('[data-article-edit-cancel]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-article-edit-cancel');
        document.getElementById('article-edit-' + id)?.classList.add('hidden');
        document.getElementById('article-preview-' + id)?.classList.remove('hidden');
    });
});
</script>
@endpush
