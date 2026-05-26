<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExampleWebsiteController extends Controller
{
    protected array $pages = [
        'features' => 'demo.features',
        'pricing' => 'demo.pricing',
        'chatbot' => 'demo.chatbot',
        'contact' => 'demo.contact',
    ];

    public function show(string $slug): View
    {
        return $this->render($slug, 'home', 'demo.home');
    }

    public function page(string $slug, string $page): View|RedirectResponse
    {
        if (! isset($this->pages[$page])) {
            return redirect()->route('demo.show', $slug);
        }

        return $this->render($slug, $page, $this->pages[$page]);
    }

    protected function render(string $slug, string $currentPage, string $view): View
    {
        $website = Website::query()
            ->where('demo_slug', $slug)
            ->where('is_active', true)
            ->where('widget_enabled', true)
            ->with('configuration')
            ->firstOrFail();

        return view($view, compact('website', 'currentPage'));
    }
}
