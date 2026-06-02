<script setup>
import LegalPolicyLayout from '../../Components/LegalPolicyLayout.vue';

const content = `
<h5>Política de Privacidade – RenovaLaser</h5>
<p><strong>Última atualização: 27/07/2025</strong></p>
<p>Na <strong>RenovaLaser</strong>, privacidade e segurança são prioridades e nos comprometemos com a transparência no tratamento de dados pessoais dos nossos clientes, parceiros e usuários. Esta Política de Privacidade estabelece como coletamos, usamos e protegemos suas informações, em conformidade com a <strong>Lei nº 13.709/2018 (Lei Geral de Proteção de Dados Pessoais - LGPD)</strong>.</p>
<h5>1. Coleta de Dados Pessoais</h5>
<p>Coletamos as seguintes informações, quando fornecidas por você ou geradas durante o uso de nossos serviços:</p>
<ul>
<li><p>Nome completo, e-mail, telefone e CPF;</p></li>
<li><p>Dados de endereço e localização (quando necessário);</p></li>
<li><p>Informações de pagamento e transações;</p></li>
<li><p>Informações de navegação, IP, tipo de dispositivo, data e hora de acesso.</p></li>
</ul>
<h5>2. Finalidade do Tratamento</h5>
<p>Utilizamos seus dados para:</p>
<ul>
<li><p>Prestar nossos serviços com qualidade e segurança;</p></li>
<li><p>Atender suas solicitações e fornecer suporte;</p></li>
<li><p>Enviar comunicações institucionais, promocionais ou informativas;</p></li>
<li><p>Cumprir obrigações legais e regulatórias.</p></li>
</ul>
<h5>3. Compartilhamento de Dados</h5>
<p>A RenovaLaser não compartilha seus dados com terceiros, exceto:</p>
<ul>
<li><p>Com parceiros e fornecedores necessários para execução de nossas atividades (ex: plataformas de pagamento, sistemas de envio de e-mails);</p></li>
<li><p>Mediante ordem judicial ou obrigação legal.</p></li>
</ul>
<p>Todos os terceiros contratados são cuidadosamente avaliados e obrigados a garantir a proteção adequada dos seus dados.</p>
<h5>4. Armazenamento e Segurança</h5>
<p>Seus dados são armazenados em ambiente seguro, com acesso restrito e controlado. Adotamos práticas alinhadas com os padrões técnicos e regulatórios para proteger seus dados pessoais.</p>
<h5>5. Direitos dos Titulares</h5>
<p>Nos termos da LGPD, você pode, a qualquer momento:</p>
<ul>
<li><p>Confirmar a existência de tratamento;</p></li>
<li><p>Acessar seus dados;</p></li>
<li><p>Corrigir dados incompletos ou desatualizados;</p></li>
<li><p>Solicitar a exclusão de dados desnecessários ou excessivos;</p></li>
<li><p>Revogar seu consentimento;</p></li>
<li><p>Solicitar a portabilidade dos dados.</p></li>
</ul>
<p>Para exercer esses direitos, entre em contato pelo e-mail <strong><a href="mailto:contato@renovalaser.com.br">contato@renovalaser.com.br</a></strong>.</p>
<h5>6. Cookies e Tecnologias de Rastreamento</h5>
<p>Utilizamos cookies para melhorar a funcionalidade do nosso site e oferecer uma melhor experiência. Você pode, a qualquer momento, configurar seu navegador para bloquear ou alertar sobre o uso de cookies.</p>
<h5>7. Alterações nesta Política</h5>
<p>Reservamo-nos o direito de alterar esta Política de Privacidade a qualquer momento. As alterações serão publicadas em nosso site com a data de atualização.</p>
<h5>8. Contato</h5>
<p>Se você tiver dúvidas, solicitações ou reclamações relacionadas ao uso dos seus dados pessoais, entre em contato conosco:</p>
<p><strong><a href="mailto:contato@renovalaserdepilacao.com.br">contato@renovalaserdepilacao.com.br</a></strong></p>
`;
</script>

<template>
    <LegalPolicyLayout
        title="Política de Privacidade"
        description="Política de privacidade da RenovaLaser, com informações sobre coleta, uso, compartilhamento, cookies e direitos dos titulares."
        canonical="/politica-de-privacidade-2"
        :content="content"
    />
</template>
