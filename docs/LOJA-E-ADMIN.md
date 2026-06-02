# Renova Laser — Loja, Checkout PagBank, Recuperação de Carrinho e Admin

Guia operacional do que foi construído (loja + blog + painel administrativo + checkout
transparente PagBank + recuperação de carrinho abandonado).

## Visão geral

- **Stack:** Laravel 13 + Inertia + Vue 3 + Tailwind v4. Banco MySQL (`renovasitenovo`).
- **Pagamento:** PagBank **Orders API** (checkout transparente: cartão tokenizado + PIX QR).
- **Filas:** driver `database` (e-mails de recuperação e confirmação são enfileirados).
- **Agendador:** detecção de carrinho abandonado roda a cada 5 min.

## Configuração do .env

```env
APP_NAME="Renova Laser Depilação"
APP_URL=https://seu-dominio.com.br

# PagBank (obtenha no painel app.pagseguro.com / developer.pagbank.com.br)
PAGBANK_ENV=sandbox            # sandbox p/ testes, production p/ valer
PAGBANK_TOKEN=                 # token da conta (Bearer)
PAGBANK_PUBLIC_KEY=            # chave pública p/ criptografar o cartão no navegador
PAGBANK_WEBHOOK_TOKEN=         # token p/ validar o x-authenticity-token do webhook
PAGBANK_PIX_DISCOUNT_PERCENT=5
PAGBANK_MAX_INSTALLMENTS=12

# Recuperação de carrinho
CART_RECOVERY_ENABLED=true
CART_ABANDON_AFTER_MINUTES=60
CART_RECOVERY_DISCOUNT_PERCENT=10
CART_RECOVERY_COUPON_TTL_HOURS=48

# E-mail (em produção troque de "log" para SMTP/Resend/SES)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="contato@renovalaserdepilacao.com.br"
MAIL_FROM_NAME="${APP_NAME}"
```

> A página **Admin → Configurações** mostra quais credenciais do PagBank estão
> presentes (sem expor os valores) e permite ajustar desconto Pix, parcelas e
> regras de recuperação de carrinho.

## Como rodar (desenvolvimento)

```bash
php artisan migrate --seed      # cria tabelas + admin
npm run build                   # ou: npm run dev (HMR)
php artisan serve               # http://127.0.0.1:8000
php artisan queue:work          # processa e-mails enfileirados
php artisan schedule:work       # dispara a recuperação de carrinho (dev)
```

Admin padrão (definido por `ADMIN_EMAIL` / `ADMIN_PASSWORD` no `.env`).

## Produção — processos que precisam estar no ar

1. **Queue worker** (e-mails): `php artisan queue:work --tries=3` (via Supervisor).
2. **Scheduler** (carrinho abandonado): uma entrada de cron:
   ```
   * * * * * cd /caminho/projeto && php artisan schedule:run >> /dev/null 2>&1
   ```
3. **Webhook PagBank:** aponte as notificações para
   `https://seu-dominio/webhooks/pagbank` e configure `PAGBANK_WEBHOOK_TOKEN`.

## Fluxo de venda (checkout transparente)

1. **Identificação (e-mail-first):** o e-mail é gravado no carrinho ao iniciar o
   checkout (`POST /checkout/identificacao`) — é o que permite a recuperação.
2. **Dados do comprador:** nome, telefone, CPF.
3. **Pagamento, dentro do site:**
   - **PIX:** gera QR Code + copia-e-cola na página de retorno, com polling
     automático (`/pedido/{n}/status`) que confirma sozinho quando pago.
   - **Cartão:** criptografado no navegador (SDK PagBank) → `charges` na Orders API,
     com seleção de parcelas. O PAN nunca passa pelo servidor.
4. Ao confirmar o pagamento, o cliente recebe **e-mail de confirmação** e o estoque
   (quando gerenciado) é baixado.

## Recuperação de carrinho abandonado

- Carrinho com e-mail + itens, inativo por `CART_ABANDON_AFTER_MINUTES`, é marcado
  `abandoned` pelo comando `cart:send-recovery`.
- É cunhado um **cupom único** de `CART_RECOVERY_DISCOUNT_PERCENT`% (validade
  `CART_RECOVERY_COUPON_TTL_HOURS`h, uso único) e enviado um e-mail com o link
  `/carrinho/recuperar/{token}`.
- Ao abrir o link, o carrinho é restaurado na sessão e o cupom aplicado
  automaticamente.
- Rodar manualmente: `php artisan cart:send-recovery`.

## Painel administrativo (`/admin`)

Protegido por `auth` + middleware `admin` (coluna `users.is_admin`).

- **Dashboard:** métricas operacionais.
- **Pedidos:** filtros (busca/status/pagamento), detalhe com itens e transações,
  alteração de status e **estorno via PagBank**.
- **Clientes (CRM):** cadastro completo (só nome obrigatório), foto, aniversário,
  tratamentos com progresso de sessões, histórico e pedidos.
- **Agenda:** calendário dia/semana (07h–22h, slots 15min), CRUD de sessões.
- **Profissionais:** CRUD (opcional nos agendamentos).
- **Produtos / Categorias / Blog:** CRUD com paginação. Produto pode ser "pacote
  de sessões" (is_treatment + nº de sessões + duração).
- **Cupons:** CRUD completo.
- **Relatórios:** receita 30d, ticket médio, receita/dia, top produtos, cupons.

## CRM + Agenda de sessões (clínica)

- **Cliente↔Tratamento↔Sessões:** um cliente tem tratamentos (ex.: 10 sessões de
  axila). Cada tratamento controla `total_sessions`/`completed_sessions`.
- **Auto-provisionamento:** quando um pedido com produto-pacote é **pago**, o
  tratamento é criado automaticamente no CRM (idempotente). Também é possível
  associar manualmente no perfil do cliente.
- **Agendamento:** `/admin/appointments` — visões dia/semana, navegação, filtro por
  profissional. Cada sessão liga cliente + (opcional) profissional + (opcional)
  tratamento. Bloqueia conflito de horário do mesmo profissional.
- **Baixa de sessão:** marcar "Compareceu" avança o tratamento e grava a última
  visita do cliente. "Faltou" registra no-show.

## Mensagens automáticas (PREPARADO, envio a conectar)

A lógica de seleção e o agendador já existem; falta conectar um canal
(WhatsApp/e-mail) em `ClinicMessenger::send()`. Mantenha `CLINIC_MESSAGING_ENABLED=false`
até lá. Comandos:
- `clinic:send-messages --type=reminder` (lembrete antes da sessão, horário)
- `clinic:send-messages --type=birthday` (feliz aniversário, diário)
- `clinic:send-messages --type=no_show` (cobrança de ausentes, diário)
- `--dry-run` simula sem enviar. Já registrados no scheduler.
- **Configurações:** loja, frete, recuperação de carrinho, parâmetros PagBank.

## Migração WordPress/WooCommerce (já existente)

Importadores via leitura direta do MySQL do WP (`wpk7_`):

```bash
php artisan store:import-woocommerce     # produtos + categorias
php artisan blog:import-wordpress        # posts + termos + Yoast SEO
php artisan seo:import-redirects         # redirects 301 do Yoast
```

## Testes

```bash
php artisan test          # 62 testes (segurança, checkout, PagBank, recuperação, admin)
vendor/bin/pint --dirty   # estilo de código
```
