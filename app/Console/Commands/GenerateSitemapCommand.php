<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSitemapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:generate-sitemap {--base-url= : URL base do site (padrão: config app.url)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera o arquivo estático public/sitemap.xml para indexação no Google e Bing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $baseUrl = rtrim((string) ($this->option('base-url') ?: config('app.url')), '/');

        if (empty($baseUrl)) {
            $this->error('A URL base do site não está configurada em app.url nem foi passada como opção.');

            return self::FAILURE;
        }

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

        $destinationPath = public_path('sitemap.xml');
        file_put_contents($destinationPath, $xml);

        $this->info("Arquivo sitemap.xml gerado com sucesso em: {$destinationPath}");
        $this->table(['URL', 'Frequência', 'Prioridade'], array_map(fn ($u) => [$u['loc'], $u['changefreq'], $u['priority']], $urls));

        return self::SUCCESS;
    }
}
