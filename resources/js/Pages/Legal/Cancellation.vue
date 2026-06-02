<script setup>
import LegalPolicyLayout from '../../Components/LegalPolicyLayout.vue';

const content = `
<h5>Política de Cancelamento de Compra</h5>
<p><strong>Renova Laser Depilação</strong></p>
<p>Na Renova Laser Depilação, prezamos pela transparência, segurança e respeito aos nossos clientes. Esta política estabelece as condições para cancelamento de compras realizadas em nossas unidades físicas ou canais digitais.</p>
<hr>
<h5>1. Direito de Arrependimento (Compras Online ou Fora da Clínica)</h5>
<p>Conforme o artigo 49 do Código de Defesa do Consumidor, compras realizadas fora do estabelecimento físico (site, WhatsApp, link de pagamento ou redes sociais) poderão ser canceladas no prazo de <strong>até 7 (sete) dias corridos</strong>, contados a partir da data da compra ou da assinatura do contrato.</p>
<p>Condições:</p>
<ul>
<li><p>O cancelamento deve ser solicitado formalmente pelos canais oficiais da clínica.</p></li>
<li><p>Não pode ter havido utilização de sessões.</p></li>
<li><p>O estorno será realizado na mesma forma de pagamento utilizada, respeitando os prazos da operadora do cartão ou instituição financeira.</p></li>
</ul>
<hr>
<h5>2. Compras Realizadas Presencialmente na Clínica</h5>
<p>Para compras realizadas presencialmente na unidade:</p>
<ul>
<li><p>Não se aplica o direito de arrependimento automático de 7 dias.</p></li>
<li><p>O cancelamento poderá ser solicitado, porém estará sujeito à análise administrativa.</p></li>
<li><p>Poderão ser aplicadas taxas administrativas previstas em contrato.</p></li>
</ul>
<hr>
<h5>3. Cancelamento de Pacotes com Sessões Já Utilizadas</h5>
<p>Em caso de desistência após início do tratamento:</p>
<ul>
<li><p>As sessões realizadas serão recalculadas pelo <strong>valor unitário vigente (sem desconto promocional)</strong>.</p></li>
<li><p>Poderá ser aplicada taxa administrativa de até 10% sobre o saldo remanescente.</p></li>
<li><p>O valor restante será devolvido conforme forma de pagamento original.</p></li>
</ul>
<hr>
<h5>4. Estornos e Prazos</h5>
<ul>
<li><p>Pagamentos via cartão de crédito: o estorno seguirá o prazo da operadora (podendo aparecer em até 2 faturas subsequentes).</p></li>
<li><p>Pix ou transferência: reembolso em até 10 dias úteis.</p></li>
<li><p>Parcelamentos: será feito o cancelamento das parcelas futuras e devolução proporcional quando aplicável.</p></li>
</ul>
<hr>
<h5>5. Promoções e Condições Especiais</h5>
<p>Compras realizadas em campanhas promocionais, combos ou condições especiais poderão ter regras específicas descritas na oferta ou contrato.</p>
<hr>
<h5>6. Disposições Gerais</h5>
<p>A Renova Laser Depilação reserva-se o direito de analisar cada solicitação individualmente, sempre com base na legislação vigente e nas cláusulas contratuais firmadas no momento da compra.</p>
<hr>
<h5>Nosso compromisso</h5>
<p>Nosso objetivo é manter uma relação transparente e justa com cada cliente, garantindo segurança jurídica e qualidade no atendimento.</p>
<p>Em caso de dúvidas, nossa equipe está à disposição.</p>
<p><strong>Renova Laser Depilação</strong><br>Tecnologia, segurança e resultados com responsabilidade.</p>
`;
</script>

<template>
    <LegalPolicyLayout
        title="Política de cancelamento"
        description="Política de cancelamento de compras da Renova Laser Depilação, com regras de arrependimento, estornos e pacotes."
        canonical="/cancelamento"
        :content="content"
    />
</template>
