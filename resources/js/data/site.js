// Conteudo verbatim extraido do site atual (renovalaserdepilacao.com.br).
const SITE = 'https://renovalaserdepilacao.com.br';

export const WHATSAPP = {
    phone: '5511988794514',
    agendar:
        'https://wa.me/5511988794514?text=Ol%C3%A1%2C%20gostaria%20de%20agendar%20uma%20avalia%C3%A7%C3%A3o.',
    especialista:
        'https://wa.me/5511988794514?text=Ol%C3%A1!%20%F0%9F%98%8A%20Tenho%20algumas%20d%C3%BAvidas%20sobre%20os%20tratamentos%20da%20RenovaLaser%20e%20gostaria%20de%20falar%20com%20uma%20especialista%2C%20pode%20me%20ajudar%3F',
    atendimento:
        'https://wa.me/5511988794514?text=Ol%C3%A1!%20%F0%9F%98%8A%20Estou%20entrando%20em%20contato%20com%20a%20Central%20de%20Atendimento%20da%20RenovaLaser.Gostaria%20de%20ajuda%20com%3A%20%5Bagendamento%20%7C%20d%C3%BAvidas%20%7C%20suporte%5DPode%20me%20orientar%3F',
    vendas:
        'https://wa.me/5511988794514?text=Ol%C3%A1!%20Estou%20interessada%20em%20fazer%20depila%C3%A7%C3%A3o%20a%20laser%2C%20mas%20quero%20ajuda%20pra%20escolher%20o%20combo%20ideal%20pra%20mim%2C%20pode%20me%20orientar%3F',
};

export const SOCIAL = {
    instagram: 'https://www.instagram.com/renovalaserdepilacao',
    tiktok: 'https://www.tiktok.com/@renovalaserdepilacao',
};

// TrustIndex (avaliacoes do Google My Business).
// Cole aqui o ID do widget — a parte final da URL do loader no codigo de
// incorporacao do TrustIndex: https://cdn.trustindex.io/loader.js?XXXXXXXX
// Pegue em https://app.trustindex.io > seu widget > "Get the code".
// (No site WP atual o plugin usa loader.js?ver=1, que NAO expoe esse ID —
//  por isso e preciso gerar/copiar o codigo standalone do widget.)
export const TRUSTINDEX_WIDGET_ID = 'c4df770739e31141ae767dcfd9a';

export const NAV = {
    primary: [
        { label: 'Quem somos', href: '/quem-somos' },
        { label: 'Nossa tecnologia', href: '/nossa-tecnologia' },
    ],
    secondary: [
        { label: 'Depilação Feminina', href: '/depilacao-feminina' },
        { label: 'Depilação Masculina', href: '/depilacao-masculina' },
        { label: 'Minhas compras', href: `${SITE}/minha-conta/` },
    ],
    account: {
        line1: 'acesse sua conta',
        or: 'ou',
        post: 'cadastre-se',
        href: `${SITE}/minha-conta/`,
    },
};

export const FEATURES = [
    { icon: 'fa-solid fa-heart', title: 'Atendimento humanizado', text: 'Equipe profissional e carismática' },
    { icon: 'fa-solid fa-eye', title: 'Resultados visíveis', text: 'Já na primeira sessão' },
    { icon: 'fa-solid fa-microchip', title: 'Tecnologia de ponta', text: 'Resultados mais rápidos' },
    { icon: 'fa-solid fa-hand-sparkles', title: 'Pode ser aplicado', text: 'em todos os tons de pele' },
    { icon: 'fa-solid fa-snowflake', title: 'Ponteira ICE', text: 'Mais conforto na sua sessão' },
];

export const PRICING = [
    {
        title: 'Combos promocionais',
        text: 'Mais áreas, mais desconto! Aproveite os combos pensados pra sua rotina e seu bolso.',
        cta: 'Ver combos',
        image: '/images/area-pernas.png',
        popup: 'combos',
    },
    {
        title: 'Pacotes de depilação',
        text: 'Ideal pra quem quer iniciar o tratamento e já garantir um preço menor por sessão.',
        cta: 'Ver pacotes',
        image: '/images/area-virilha.png',
        popup: 'pacotes',
    },
    {
        title: 'Sessões avulsas',
        text: 'Perfeitas para quem quer experimentar ou fazer retoques, sem compromisso com pacotes.',
        cta: 'Ver ofertas',
        image: '/images/area-axilas.png',
        popup: 'ofertas',
    },
];

export const FAQ = [
    {
        q: 'Quantas sessões são necessárias?',
        a: 'Em média, são recomendadas de 6 a 10 sessões para resultados eficazes, podendo variar de acordo com a área tratada, cor e espessura dos pelos, além de fatores hormonais.',
    },
    {
        q: 'Depilação a laser dói?',
        a: 'Nossa tecnologia conta com uma ponteira resfriada a -16ºC, o desconforto durante a depilação a laser é mínimo. A sensação é suave e bem tolerável, tornando o tratamento muito mais tranquilo do que métodos tradicionais.',
    },
    {
        q: 'Depilação a laser definitiva?',
        a: 'É uma solução de longa duração! A maioria dos pelos não volta a crescer, e os que retornam são mais finos. Basta manter algumas sessões de manutenção para manter o resultado.',
    },
    {
        q: 'Como funciona?',
        a: 'A ponteira atua na camada superficial da pele, aquecendo apenas o folículo piloso para impedir o crescimento do pelo. Durante o processo, a pele é resfriada, o que garante mais conforto e segurança ao tratamento.',
    },
];

export const ABOUT_PARAGRAPHS = [
    'A Renova Laser é uma clínica de depilação a laser com marca própria ou seja, não somos franquia. E isso faz toda a diferença. Aqui, cada detalhe é pensado por quem vive o negócio todos os dias.',
    'Utilizamos a tecnologia Triple Wave (Diodo, Nd-YAG e Alexandrite), reconhecida como a mais moderna do mercado, com ponteira ice resfriada a – 16ºc que garante mais conforto no tratamento.',
    'Nosso atendimento é um dos mais bem avaliados do setor porque colocamos o cliente no centro da experiência. Equipe treinada, carismática e comprometida com resultados reais desde a primeira sessão.',
    'Se você está no Tatuapé ou região e busca segurança, eficiência e acolhimento a Renova Laser é a escolha certa.',
];

export const LOCATION = {
    city: 'Tatuapé | São Paulo / SP',
    address: 'Rua Cantagalo 223 – sala 04',
    hours: ['Seg a sex das 09h às 20h', 'Sábado das 08h às 17h'],
};

export const FOOTER = {
    columns: [
        {
            title: 'Menu',
            links: [
                { label: 'Depilação feminina', href: '/depilacao-feminina' },
                { label: 'Depilação masculina', href: '/depilacao-masculina' },
                { label: 'Minhas compras', href: `${SITE}/area-do-cliente/` },
            ],
        },
        {
            title: 'Sobre nós',
            links: [
                { label: 'Quem somos', href: '/quem-somos' },
                { label: 'Nossa tecnologia', href: '/nossa-tecnologia' },
                { label: 'Blog', href: `${SITE}/blog/` },
            ],
        },
        {
            title: 'Ajuda',
            links: [
                { label: 'Central de atendimento', href: WHATSAPP.atendimento },
                { label: 'Central de vendas', href: WHATSAPP.vendas },
            ],
        },
    ],
    policies: [
        { label: 'Política de Privacidade e Cookies', href: `${SITE}/politica-de-privacidade-2/` },
        { label: 'Política de Reembolso', href: `${SITE}/politica-de-reembolso/` },
        { label: 'Política de Cancelamento', href: `${SITE}/cancelamento/` },
    ],
};
