<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AgentPwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $config = config('chatbot.pwa');

        return response()->json([
            'name' => $config['name'],
            'short_name' => $config['short_name'],
            'description' => $config['description'],
            'start_url' => route('inbox.index'),
            'scope' => url('/'),
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => $config['background_color'],
            'theme_color' => $config['theme_color'],
            'categories' => ['business', 'productivity'],
            'icons' => [
                [
                    'src' => route('agent.pwa.icon', ['size' => 192]),
                    'sizes' => '192x192',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any',
                ],
                [
                    'src' => route('agent.pwa.icon', ['size' => 512]),
                    'sizes' => '512x512',
                    'type' => 'image/svg+xml',
                    'purpose' => 'maskable',
                ],
            ],
        ])->header('Content-Type', 'application/manifest+json');
    }

    public function icon(int $size = 192): Response
    {
        $size = in_array($size, [192, 512], true) ? $size : 192;
        $color = config('chatbot.pwa.theme_color', '#2563eb');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 512 512">
  <rect width="512" height="512" rx="96" fill="{$color}"/>
  <path fill="#fff" d="M128 160h256a32 32 0 0 1 32 32v128a32 32 0 0 1-32 32H216l-72 56v-56h-16a32 32 0 0 1-32-32V192a32 32 0 0 1 32-32z"/>
  <circle cx="208" cy="256" r="20" fill="{$color}"/>
  <circle cx="256" cy="256" r="20" fill="{$color}"/>
  <circle cx="304" cy="256" r="20" fill="{$color}"/>
</svg>
SVG;

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}
