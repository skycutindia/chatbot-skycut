<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\AuthorizesTenantRole;
use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Services\WidgetPluginExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WidgetEmbedController extends Controller
{
    use AuthorizesTenantRole;

    public function show(Website $website, WidgetPluginExportService $export): View
    {
        $this->ensureWebsiteInOrganization(request(), $website);

        if (! is_file($export->installGuidePath($website))) {
            $export->persistPackage($website);
        }

        return view('dashboard.websites.embed', [
            'website' => $website,
            'embedSnippet' => $website->embedSnippet(),
            'simpleSnippet' => $website->simpleEmbedSnippet(),
            'initSnippet' => $this->initSnippet($website),
            'wordpressSnippet' => $this->wordpressSnippet($website),
            'shopifySnippet' => $this->shopifySnippet($website),
            'hasInstallGuide' => is_file($export->installGuidePath($website)),
        ]);
    }

    public function download(Website $website, WidgetPluginExportService $export): BinaryFileResponse
    {
        $this->ensureCanManageWebsites(request());
        $this->ensureWebsiteInOrganization(request(), $website);

        $path = $export->buildZip($website);
        $filename = 'chatbot-'.Str::slug($website->name).'.zip';

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function regenerate(Website $website, WidgetPluginExportService $export): RedirectResponse
    {
        $this->ensureCanManageWebsites(request());
        $this->ensureWebsiteInOrganization(request(), $website);

        $export->persistPackage($website);

        return back()->with('success', 'Plugin files regenerated.');
    }

    public function installGuide(Website $website, WidgetPluginExportService $export): BinaryFileResponse
    {
        $this->ensureCanManageWebsites(request());
        $this->ensureWebsiteInOrganization(request(), $website);

        $path = $export->installGuidePath($website);
        if (! is_file($path)) {
            $export->persistPackage($website);
        }

        return response()->file($path, [
            'Content-Type' => 'text/html',
        ]);
    }

    public function downloadReadme(Website $website, WidgetPluginExportService $export): BinaryFileResponse
    {
        $this->ensureCanManageWebsites(request());
        $this->ensureWebsiteInOrganization(request(), $website);

        $path = $export->readmePath($website);
        if (! is_file($path)) {
            $export->persistPackage($website);
        }

        return response()->download($path, 'README.txt');
    }

    protected function wordpressSnippet(Website $website): string
    {
        return sprintf(
            "// Add to your theme's footer.php or use a header/footer plugin:\n%s",
            $website->embedSnippet()
        );
    }

    protected function shopifySnippet(Website $website): string
    {
        return sprintf(
            "{%% comment %%} Add to theme.liquid before </body> {%% endcomment %%}\n%s",
            $website->embedSnippet()
        );
    }

    public function initSnippet(Website $website): string
    {
        $base = rtrim(config('app.url'), '/');

        return <<<HTML
<script src="{$base}/widget/loader.js" data-bot-token="{$website->bot_token}" async></script>
<script>
window.ChatFlow = window.ChatFlow || {};
window.ChatFlow.init = function(opts) {
  console.info('[ChatFlow] bot_id:', opts.bot_id || '{$website->bot_token}', 'website_id:', opts.website_id || '{$website->id}');
};
window.ChatFlow.init({ bot_id: '{$website->bot_token}', website_id: '{$website->id}' });
</script>
HTML;
    }
}
