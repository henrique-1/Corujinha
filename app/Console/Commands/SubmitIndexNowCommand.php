<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SubmitIndexNowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:bing-index {--url= : URL específica para submeter} {--base-url= : URL base do site (padrão: config app.url ou https://corujinha.bissoli.tech)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Submete as URLs públicas principais ao Microsoft Bing via IndexNow para indexação rápida';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $key = config('services.indexnow.key');

        if (empty($key)) {
            $this->error('INDEXNOW_KEY não está configurada no config/services.php ou .env.');

            return self::FAILURE;
        }

        $configuredUrl = config('app.url');
        $defaultUrl = (! empty($configuredUrl) && ! str_contains($configuredUrl, 'localhost'))
            ? $configuredUrl
            : 'https://corujinha.bissoli.tech';

        $baseUrl = rtrim((string) ($this->option('base-url') ?: $defaultUrl), '/');
        $host = parse_url($baseUrl, PHP_URL_HOST) ?: 'corujinha.bissoli.tech';

        $urlOption = $this->option('url');
        $urls = $urlOption
            ? [(string) $urlOption]
            : [
                $baseUrl.'/',
                $baseUrl.'/sala',
            ];

        $payload = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => $baseUrl.'/'.$key.'.txt',
            'urlList' => $urls,
        ];

        $this->info('Submetendo '.count($urls).' URL(s) ao Microsoft Bing e parceiros via IndexNow...');
        $this->line("Host: {$host}");
        $this->line("Key Location: {$payload['keyLocation']}");

        // Submete aos endpoints do Bing e IndexNow
        $endpoints = [
            'https://www.bing.com/indexnow',
            'https://api.indexnow.org/indexnow',
        ];

        $success = false;

        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders(['Content-Type' => 'application/json; charset=utf-8'])
                    ->post($endpoint, $payload);

                if ($response->successful() || in_array($response->status(), [200, 202], true)) {
                    $this->info("Sucesso ao submeter para {$endpoint} (HTTP {$response->status()}).");
                    $success = true;
                } else {
                    $this->warn("Aviso: {$endpoint} retornou status {$response->status()}: ".$response->body());
                }
            } catch (\Throwable $e) {
                $this->error("Erro de conexão ao {$endpoint}: ".$e->getMessage());
            }
        }

        if ($success) {
            $this->info('Submissão ao Bing concluída com sucesso!');

            return self::SUCCESS;
        }

        $this->error('Não foi possível obter confirmação de envio ao Bing.');

        return self::FAILURE;
    }
}
