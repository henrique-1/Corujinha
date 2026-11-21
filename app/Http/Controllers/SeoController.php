<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Request;

class SeoController extends Controller
{
    /**
     * Retorna o sitemap.xml dinâmico em conformidade com o Bing e padrões Sitemaps.org.
     */
    public function sitemap(): Response
    {
        $configuredUrl = config('app.url');
        $baseUrl = (! empty($configuredUrl) && ! str_contains($configuredUrl, 'localhost'))
            ? rtrim($configuredUrl, '/')
            : (Request::getHost() && ! in_array(Request::getHost(), ['localhost', '127.0.0.1'], true)
                ? rtrim(Request::root(), '/')
                : 'https://corujinha.bissoli.tech');

        $now = now()->toAtomString();

        $urls = [
            [
                'loc' => $baseUrl.'/',
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
            [
                'loc' => $baseUrl.'/sala',
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
            [
                'loc' => $baseUrl.'/login',
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
        ];

        $xml = view('seo.sitemap', compact('urls'))->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Serve o arquivo de validação de chave pública do IndexNow exigido pelo Bing (ex.: /{key}.txt).
     */
    public function indexNowKeyFile(string $key): Response
    {
        $configuredKey = config('services.indexnow.key');

        if (empty($configuredKey) || $key !== $configuredKey) {
            abort(404);
        }

        return response($configuredKey, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
