<script setup>
import { Head } from '@inertiajs/vue3';
import SiteLayout from '../Layouts/SiteLayout.vue';
import FeaturesStrip from '../Components/FeaturesStrip.vue';
import YoutubeHeroBackground from '../Components/YoutubeHeroBackground.vue';

const benefits = [
    {
        title: 'Três lasers em um',
        text: 'trata diferentes profundidades e estruturas do pelo em um único disparo.',
    },
    {
        title: 'Indicado para todos os tipos de pele',
        text: 'inclusive negras e bronzeadas.',
    },
    {
        title: 'Mais conforto',
        text: 'ponteira resfriada a -16ºC que protege a pele e reduz a dor.',
    },
    {
        title: 'Resultados mais rápidos',
        text: 'sessões mais curtas e eficientes.',
    },
    {
        title: 'Sem complicações no preparo',
        text: 'basta raspar com lâmina no dia anterior. Não precisa deixar os pelos crescerem!',
    },
];
</script>

<template>
    <Head title="Nossa tecnologia" />

    <SiteLayout>
        <section class="relative isolate flex min-h-screen flex-col justify-center overflow-hidden lg:min-h-[700px]">
            <YoutubeHeroBackground video-id="gJULk8nCMfg" title="Video de fundo - Nossa tecnologia" />

            <div
                class="absolute inset-0 -z-10 bg-white opacity-20 lg:opacity-50"
                style="mix-blend-mode: color;"
            ></div>

            <div class="mx-auto flex w-full max-w-[1200px] translate-y-14 flex-col justify-end px-5 pb-24 pt-40 text-left lg:translate-y-0 lg:justify-center lg:py-44">
                <p class="font-display text-[70px] font-semibold leading-none tracking-[1px] text-white lg:text-[96px] lg:leading-[96px] lg:tracking-normal">
                    Nossa
                </p>
                <h1 class="font-display text-[51px] font-semibold lowercase leading-[0.9] tracking-[1px] text-white lg:text-[69px] lg:leading-[69px] lg:tracking-normal">
                    tecnologia
                </h1>
            </div>
        </section>

        <FeaturesStrip />

        <section class="flex min-h-[400px] items-center bg-white px-[18px] pb-[150px] pt-[65px] lg:px-5 lg:pb-[90px] lg:pt-24">
            <div class="mx-auto max-w-[980px] text-center font-montserrat text-[15px] leading-relaxed text-muted">
                <h2 class="mx-auto max-w-[860px] text-center font-sans text-[20px] font-extrabold leading-tight text-heading-soft lg:text-[39px]">
                    Tecnologia de ponta, conforto real e resultados duradouros
                </h2>

                <div class="mt-8 space-y-4 lg:mt-[70px]">
                    <p>
                        Na <strong class="font-bold">Renova Laser</strong>, levamos a sério sua experiência e seus resultados. Por isso, investimos no que há de mais moderno em depilação a laser: o <strong class="font-bold">VEGA Triple Wave</strong>, uma tecnologia avançada que combina <strong class="font-bold">três tipos de laser em um único disparo</strong>: <strong class="font-bold">Alexandrite, Diodo e Nd-YAG</strong>. Isso garante <strong class="font-bold">eficiência superior</strong>, mais conforto e <strong class="font-bold">resultados visíveis desde as primeiras sessões</strong>, independentemente do seu tom de pele ou espessura dos pelos.
                    </p>
                    <p>
                        Enquanto outras clínicas trabalham com tecnologias isoladas, como <strong class="font-bold">Soprano Ice</strong> ou <strong class="font-bold">Light Sheer</strong>, que utilizam apenas um tipo de laser, o VEGA Triple Wave entrega o melhor dos três mundos ao mesmo tempo.
                    </p>
                </div>

                <h2 class="mx-auto mt-10 max-w-[760px] text-center font-sans text-[20px] font-extrabold leading-tight text-heading-soft lg:text-[39px]">
                    Comparativo entre os principais lasers do mercado:
                </h2>

                <div class="mt-8 flex justify-center">
                    <img
                        class="h-auto w-full max-w-[800px]"
                        src="/images/tabela-tecnologia.png"
                        alt="Comparativo entre os principais lasers do mercado"
                        loading="lazy"
                        decoding="async"
                        width="800"
                        height="440"
                    >
                </div>

                <h2 class="mx-auto mt-12 max-w-[760px] text-center font-sans text-[20px] font-extrabold leading-tight text-heading-soft lg:text-[39px]">
                    Por que o VEGA Triple Wave é a melhor escolha?
                </h2>

                <ul class="mt-8 space-y-3">
                    <li v-for="benefit in benefits" :key="benefit.title" class="flex justify-center gap-3">
                        <i class="fa-solid fa-check mt-1 text-[14px] text-brand"></i>
                        <span>
                            <strong class="font-bold text-muted">{{ benefit.title }}</strong>:
                            {{ benefit.text }}
                        </span>
                    </li>
                </ul>

                <h2 class="mx-auto mt-12 max-w-[760px] text-center font-sans text-[20px] font-extrabold leading-tight text-heading-soft lg:text-[39px]">
                    Escolha tecnologia de verdade, escolha RenovaLaser
                </h2>

                <p class="mt-8">
                    Com o VEGA Triple Wave, você tem a certeza de estar investindo em uma tecnologia completa, segura e eficaz. Deixe para trás métodos antigos, demorados e desconfortáveis. A <strong class="font-bold">Renova Laser</strong> entrega um tratamento moderno, acolhedor e pensado nos mínimos detalhes para o seu bem-estar.
                </p>

            </div>
        </section>
    </SiteLayout>
</template>
