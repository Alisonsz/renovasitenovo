<script setup>
import { Head } from '@inertiajs/vue3';
import SiteLayout from '../Layouts/SiteLayout.vue';
import FeaturesStrip from '../Components/FeaturesStrip.vue';

const paragraphs = [
    {
        text: 'Na Renova Laser Depilação, acreditamos que cuidar de si mesma(o) vai muito além da estética, é sobre bem-estar, liberdade e autoestima.',
        strong: ['Renova Laser Depilação'],
    },
    {
        text: 'Somos uma clínica especializada em depilação a laser de alta performance, localizada no coração do Tatuapé. Utilizamos a tecnologia VEGA Triple Wave, que combina os três tipos de laser mais eficazes do mercado (Diodo, Alexandrite e Nd-YAG), garantindo resultados mais rápidos, eficazes e seguros para todos os tipos de pele.',
        strong: ['depilação a laser de alta performance', 'VEGA Triple Wave'],
    },
    {
        text: 'Nosso atendimento é 100% personalizado, com profissionais treinadas para oferecer um tratamento humanizado, acolhedor e respeitoso. Aqui, cada cliente é único e fazemos questão de entender suas necessidades para indicar o melhor plano de tratamento.',
        strong: ['100% personalizado'],
    },
    {
        text: 'Não somos franquia. A Renova Laser é uma marca própria, construída com carinho e dedicação, o que nos permite manter um padrão de qualidade mais alto, com mais atenção aos detalhes e à experiência de cada pessoa que passa por aqui.',
        strong: ['Renova Laser é uma marca própria'],
    },
];

const checks = [
    'Resultados reais desde as primeiras sessões',
    'Equipamentos de última geração',
    'Preços justos e acessíveis',
    'Avaliações excelentes nas plataformas online',
];

function parts(paragraph) {
    let segments = [{ value: paragraph.text, strong: false }];

    paragraph.strong.forEach((term) => {
        segments = segments.flatMap((segment) => {
            if (segment.strong || !segment.value.includes(term)) return [segment];
            const split = segment.value.split(term);
            return split.flatMap((piece, index) => {
                const next = [];
                if (piece) next.push({ value: piece, strong: false });
                if (index < split.length - 1) next.push({ value: term, strong: true });
                return next;
            });
        });
    });

    return segments;
}
</script>

<template>
    <Head title="Quem somos" />

    <SiteLayout>
        <section class="relative isolate flex min-h-screen flex-col justify-center overflow-hidden lg:min-h-[700px]">
            <video
                class="absolute inset-0 -z-20 h-full w-full object-cover"
                src="/images/hero-renova-laser-depilacao.mp4"
                autoplay
                muted
                loop
                playsinline
                poster="/images/hero-bg.jpg"
                preload="metadata"
            ></video>

            <div
                class="absolute inset-0 -z-10 bg-white opacity-20 lg:opacity-50"
                style="mix-blend-mode: color;"
            ></div>

            <div class="mx-auto flex w-full max-w-[1200px] translate-y-14 flex-col justify-end px-5 pb-24 pt-40 text-left lg:translate-y-0 lg:justify-center lg:py-44">
                <p class="font-display text-[41px] font-semibold leading-none tracking-[1px] text-white lg:text-[90px] lg:leading-[90px] lg:tracking-normal">
                    Quem
                </p>
                <h1 class="font-display text-[68px] font-semibold lowercase leading-[0.9] tracking-[1px] text-white lg:text-[90px] lg:leading-[90px] lg:tracking-normal">
                    somos
                </h1>
            </div>
        </section>

        <FeaturesStrip />

        <section class="flex min-h-[400px] items-center bg-white px-[18px] pb-16 pt-[25px] lg:px-5 lg:py-24">
            <div class="mx-auto max-w-[980px] font-montserrat text-[15px] leading-relaxed text-muted">
                <p v-for="paragraph in paragraphs" :key="paragraph.text" class="mb-4">
                    <template v-for="segment in parts(paragraph)" :key="segment.value">
                        <strong v-if="segment.strong" class="font-bold text-muted">{{ segment.value }}</strong>
                        <span v-else>{{ segment.value }}</span>
                    </template>
                </p>

                <ul class="mb-4 space-y-1">
                    <li v-for="item in checks" :key="item" class="flex gap-2">
                        <i class="fa-solid fa-check mt-1 text-[13px] text-brand"></i>
                        <span>{{ item }}</span>
                    </li>
                </ul>

                <p class="mb-4">
                    Seja sua primeira vez com depilação a laser ou se você já passou por outras clínicas, te convidamos a conhecer um novo padrão de atendimento e resultado. A Renova Laser é para quem busca segurança, conforto e eficácia, tudo no mesmo lugar.
                </p>
                <p class="font-semibold text-heading-soft">
                    Sua pele renovada começa aqui.
                </p>
            </div>
        </section>
    </SiteLayout>
</template>
