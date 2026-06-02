<script setup>
import LegalPolicyLayout from '../../Components/LegalPolicyLayout.vue';

const content = `
<h5>Política de Reembolso</h5>
<h5><strong>Última atualização: 27/07/2025</strong></h5>
<p>Na <strong>Renova Laser Depilação</strong>, prezamos pela transparência e respeito ao consumidor. Esta Política de Reembolso estabelece as regras e condições para devolução de valores relacionados a nossos serviços, de acordo com o Código de Defesa do Consumidor (Lei nº 8.078/1990).</p>
<h5>1. Condições para Solicitação de Reembolso</h5>
<p>O cliente poderá solicitar reembolso nas seguintes situações:</p>
<ul>
<li><p><strong>Desistência da compra</strong> em até 7 (sete) dias corridos após a contratação, quando a aquisição tiver sido realizada de forma <strong>remota</strong> (online, telefone, etc), conforme o direito de arrependimento previsto no art. 49 do CDC.</p></li>
<li><p><strong>Cancelamento de sessões não utilizadas</strong>, desde que comunicado com antecedência mínima de 48 horas da data agendada.</p></li>
<li><p><strong>Impossibilidade médica comprovada</strong>, por meio de laudo, que contraindique a continuidade do tratamento.</p></li>
</ul>
<h5>2. Situações que Não Geram Reembolso</h5>
<ul>
<li><p>Ausência do cliente no horário agendado (não comparecimento sem aviso prévio);</p></li>
<li><p>Cancelamentos informados com menos de 48 horas de antecedência;</p></li>
<li><p>Sessões já realizadas;</p></li>
<li><p>Desistência após início do tratamento sem justificativa médica;</p></li>
<li><p>Pacotes promocionais ou com condições especiais previamente informadas como <strong>não reembolsáveis</strong>.</p></li>
</ul>
<h5>3. Procedimento para Solicitação</h5>
<p>Para solicitar o reembolso, o cliente deverá:</p>
<ol>
<li><p>Enviar e-mail para <strong><a href="mailto:contato@renovalaser.com.br">contato@renovalaser.com.br</a></strong> com:</p>
<ul>
<li><p>Nome completo;</p></li>
<li><p>CPF;</p></li>
<li><p>Motivo do pedido;</p></li>
<li><p>Comprovante de pagamento;</p></li>
<li><p>Dados bancários (caso o reembolso não possa ser realizado pelo mesmo meio de pagamento utilizado).</p></li>
</ul>
</li>
<li><p>O pedido será analisado em até <strong>5 dias úteis</strong>.</p></li>
<li><p>O reembolso será processado, se aprovado, em até <strong>10 dias úteis</strong>, conforme o meio de pagamento utilizado.</p></li>
</ol>
<h5>4. Forma de Reembolso</h5>
<ul>
<li><p>Compras feitas por <strong>cartão de crédito</strong>: o estorno será solicitado à operadora e pode levar até duas faturas para constar no extrato.</p></li>
<li><p>Compras feitas por <strong>PIX ou transferência</strong>: o valor será devolvido na conta bancária indicada.</p></li>
<li><p>Pagamentos em <strong>dinheiro</strong>: devolução presencial mediante agendamento.</p></li>
</ul>
<h5>5. Cancelamento de Pacotes</h5>
<p>Em caso de pacotes com múltiplas sessões, o valor do reembolso será proporcional ao número de sessões <strong>não utilizadas</strong>, descontando eventuais benefícios, brindes ou bônus aplicados no momento da compra.</p>
<hr>
<h5>6. Considerações Finais</h5>
<p>A <strong>RenovaLaser</strong> reserva-se o direito de alterar esta política a qualquer momento, mediante publicação em nosso site ou aviso aos clientes.</p>
<p>Em caso de dúvidas, entre em contato conosco:</p>
<p><strong><a href="mailto:contato@renovalaserdepilacao.com.br">contato@renovalaserdepilacao.com.br</a></strong></p>
`;
</script>

<template>
    <LegalPolicyLayout
        title="Política de Reembolso"
        description="Política de reembolso da Renova Laser Depilação, com regras para solicitações, prazos e formas de devolução."
        canonical="/politica-de-reembolso"
        :content="content"
    />
</template>
