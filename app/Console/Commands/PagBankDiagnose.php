<?php

namespace App\Console\Commands;

use App\Services\Payments\PagBankClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;

class PagBankDiagnose extends Command
{
    protected $signature = 'pagbank:diagnose';

    protected $description = 'Diagnostica o PagBank: valida o token e tenta criar um PIX de teste, mostrando o motivo exato de qualquer falha';

    public function handle(PagBankClient $client): int
    {
        $env = (string) config('services.pagbank.env');
        $this->line('Ambiente (PAGBANK_ENV): <info>'.$env.'</info>');
        $this->line('Base URL: <info>'.($env === 'sandbox' ? 'https://sandbox.api.pagseguro.com' : 'https://api.pagseguro.com').'</info>');

        if (! $client->configured()) {
            $this->error('PAGBANK_TOKEN não configurado. Rodou "php artisan config:clear" depois de editar o .env?');

            return self::FAILURE;
        }

        $token = (string) config('services.pagbank.token');
        $this->line('Token: <info>configurado</info> ('.strlen($token).' caracteres, começa com "'.substr($token, 0, 4).'…")');

        // 1) Token validity (and env match) via public key generation.
        $this->newLine();
        $this->line('1) Validando o token (gerando a chave pública)...');
        try {
            $client->createPublicKey();
            $this->info('   ✔ Token aceito no ambiente "'.$env.'".');
        } catch (\Throwable $e) {
            $this->error('   ✘ Token recusado: '.$this->reason($e));
            $this->warn('   → Causa provável: o token não é do ambiente "'.$env.'" (sandbox × produção) ou está inválido.');

            return self::FAILURE;
        }

        // 2) Real PIX order (R$ 1,00, unpaid, expires in 30 min).
        $this->newLine();
        $this->line('2) Criando um PIX de teste (R$ 1,00, não pago, expira em 30 min)...');
        $payload = [
            'reference_id' => 'DIAG-'.Carbon::now()->format('YmdHis'),
            'customer' => [
                'name' => 'Diagnostico PagBank',
                'email' => 'diagnostico@example.com',
                'tax_id' => '11144477735', // CPF de teste válido
                'phones' => [['country' => '55', 'area' => '11', 'number' => '999999999', 'type' => 'MOBILE']],
            ],
            'items' => [['reference_id' => '1', 'name' => 'Teste diagnostico', 'quantity' => 1, 'unit_amount' => 100]],
            'qr_codes' => [['amount' => ['value' => 100], 'expiration_date' => Carbon::now()->addMinutes(30)->toIso8601String()]],
        ];

        try {
            $resp = $client->createOrder($payload);
            $qr = $resp['qr_codes'][0] ?? null;

            if ($qr) {
                $this->info('   ✔ PIX gerado com sucesso! O lado do PagBank está OK.');
                $this->line('   Order ID: '.($resp['id'] ?? '?'));
                $this->line('   Copia-e-cola: '.substr((string) ($qr['text'] ?? ''), 0, 40).'…');
                $this->newLine();
                $this->info('Conclusão: o PagBank funciona. Se o site ainda não gera PIX, o problema é:');
                $this->line('  • o código novo ainda não foi para o servidor (deploy), ou');
                $this->line('  • o CPF/dados do cliente no checkout estão inválidos.');

                return self::SUCCESS;
            }

            $this->error('   ✘ Resposta sem qr_codes: '.json_encode($resp));

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('   ✘ O PagBank recusou o PIX: '.$this->reason($e));
            $this->newLine();
            $this->warn('Esse é o motivo real do "não gera PIX". Causas comuns:');
            $this->line('  • tax_id (CPF) inválido nos dados do cliente');
            $this->line('  • notification_urls inválida (APP_URL precisa ser https público em produção)');
            $this->line('  • telefone/dados do cliente fora do formato esperado');

            return self::FAILURE;
        }
    }

    private function reason(\Throwable $e): string
    {
        if ($e instanceof RequestException && $e->response) {
            $body = $e->response->json();
            $desc = data_get($body, 'error_messages.0.description');
            $code = data_get($body, 'error_messages.0.code');
            $status = $e->response->status();
            $msg = trim(($status ? "[HTTP $status] " : '').((string) $desc).($code ? " (code: $code)" : ''));

            return $msg !== '' ? $msg : (string) $e->response->body();
        }

        return $e->getMessage();
    }
}
