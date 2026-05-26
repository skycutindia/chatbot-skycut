<?php

namespace App\Services;

use App\Models\Website;
use Illuminate\Support\Facades\File;
use ZipArchive;

class WidgetPluginExportService
{
    public function __construct(
        protected WidgetConfigService $configService,
    ) {}

    public function packageDirectory(Website $website): string
    {
        return storage_path('app/widget-plugins/'.$website->id);
    }

    /** Generate and persist widget.js, widget.css, config.json, install-guide.html, README.txt */
    public function persistPackage(Website $website): string
    {
        $website->load('configuration');
        $dir = $this->packageDirectory($website);
        File::ensureDirectoryExists($dir);

        $files = $this->buildPackageFiles($website);

        foreach ($files as $name => $contents) {
            File::put($dir.'/'.$name, $contents);
        }

        return $dir;
    }

    public function buildZip(Website $website): string
    {
        $dir = $this->persistPackage($website);
        $zipPath = storage_path('app/widget-exports/chatbot-'.$website->id.'.zip');
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create plugin ZIP.');
        }

        foreach (File::files($dir) as $file) {
            $zip->addFile($file->getPathname(), $file->getFilename());
        }
        $zip->close();

        return $zipPath;
    }

    public function installGuidePath(Website $website): string
    {
        return $this->packageDirectory($website).'/install-guide.html';
    }

    public function readmePath(Website $website): string
    {
        return $this->packageDirectory($website).'/README.txt';
    }

    /** @return array<string, string> */
    protected function buildPackageFiles(Website $website): array
    {
        $config = $this->configService->buildConfig($website);
        $baseUrl = rtrim(config('app.url'), '/');
        $token = $website->bot_token;
        $embedCode = $website->embedSnippet();

        $configJson = [
            'bot_id' => $token,
            'website_id' => (string) $website->id,
            'website_name' => $website->name,
            'api_base' => $baseUrl,
            'config_endpoint' => "{$baseUrl}/api/widget/{$token}/config",
            'version' => $config['version'] ?? time(),
            'appearance' => $config['appearance'] ?? [],
            'messages' => $config['messages'] ?? [],
        ];

        $widgetJs = $this->readPublicWidget('chatbot.js');
        $widgetCss = $this->readPublicWidget('chatbot.css');
        $loaderJs = $this->readPublicWidget('loader.js');

        return [
            'widget.js' => $widgetJs,
            'widget.css' => $widgetCss,
            'loader.js' => $loaderJs,
            'config.json' => json_encode($configJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'install-guide.html' => $this->installGuideHtml($website, $embedCode, $baseUrl),
            'README.txt' => $this->readmeText($website, $embedCode),
        ];
    }

    protected function readPublicWidget(string $file): string
    {
        $path = public_path('widget/'.$file);
        if (! File::exists($path)) {
            return "/* {$file} not found — deploy public/widget/{$file} */\n";
        }

        return File::get($path);
    }

    protected function installGuideHtml(Website $website, string $embedCode, string $baseUrl): string
    {
        $name = e($website->name);
        $code = e($embedCode);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{$name} — Chatbot Installation</title>
  <style>body{font-family:system-ui,sans-serif;max-width:720px;margin:2rem auto;padding:0 1rem;line-height:1.6}pre{background:#f1f5f9;padding:1rem;border-radius:8px;overflow:auto}code{font-size:13px}</style>
</head>
<body>
  <h1>AI Chatbot Installation Guide</h1>
  <p>Website: <strong>{$name}</strong></p>
  <h2>Quick install</h2>
  <p>Paste this code before <code>&lt;/body&gt;</code> on every page:</p>
  <pre><code>{$code}</code></pre>
  <h2>Files in this package</h2>
  <ul>
    <li><strong>widget.js</strong> — Chat widget application</li>
    <li><strong>widget.css</strong> — Widget styles</li>
    <li><strong>loader.js</strong> — Optional async loader</li>
    <li><strong>config.json</strong> — Reference (live config loads from API)</li>
    <li><strong>README.txt</strong> — Quick reference</li>
  </ul>
  <h2>Live updates</h2>
  <p>Training, Q&amp;A, colors, and messages update automatically from your dashboard.</p>
  <p>API: {$baseUrl}/api/widget/</p>
</body>
</html>
HTML;
    }

    protected function readmeText(Website $website, string $embedCode): string
    {
        return implode("\n", [
            'AI Chatbot Hub Pro — Widget Package',
            '=====================================',
            'Website: '.$website->name,
            'Bot ID: '.$website->bot_token,
            '',
            'INSTALL',
            '-----',
            'Add the embed code from install-guide.html before </body>.',
            '',
            'EMBED CODE',
            '----------',
            $embedCode,
            '',
            'FILES',
            '-----',
            'widget.js, widget.css, config.json, install-guide.html',
            '',
            'SUPPORT',
            '-------',
            'Dashboard changes apply instantly via API config.',
        ]);
    }
}
