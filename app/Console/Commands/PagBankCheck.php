<?php

namespace App\Console\Commands;

use App\Services\Payments\PagBankClient;
use Illuminate\Console\Command;

class PagBankCheck extends Command
{
    protected $signature = 'pagbank:check';

    protected $description = 'Verifica o token do PagBank gerando a chave pública do cartão';

    public function handle(PagBankClient $client): int
    {
        $env = (string) config('services.pagbank.env');
        $this->line('Ambiente: <info>'.$env.'</info>');

        if (! $client->configured()) {
            $this->error('PAGBANK_TOKEN não configurado. Defina no .env e rode de novo.');

            return self::FAILURE;
        }

        $this->line('Token: <info>configurado</info>');
        $this->line('Gerando chave pública do cartão (POST /public-keys)...');

        try {
            $key = $client->createPublicKey();
        } catch (\Throwable $e) {
            $this->error('Falha ao chamar o PagBank: '.$e->getMessage());
            $this->warn('Confira se o token é do ambiente "'.$env.'" e está válido.');

            return self::FAILURE;
        }

        if (! $key) {
            $this->error('O PagBank respondeu, mas não retornou uma chave pública.');

            return self::FAILURE;
        }

        $this->info('✔ Token válido! Chave pública gerada com sucesso.');
        $this->line('Prévia: '.substr(preg_replace('/\s+/', '', $key), 0, 40).'...');
        $this->newLine();
        $this->line('Webhook: validado com o mesmo token — nenhuma credencial extra é necessária.');
        $this->line('URL de notificação p/ cadastrar no PagBank: <info>'.config('services.pagbank.notification_url').'</info>');

        return self::SUCCESS;
    }
}
