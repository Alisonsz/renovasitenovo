<script setup>
import { WHATSAPP } from '../data/site.js';
</script>

<template>
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

        <div class="absolute inset-0 -z-10 bg-black/10"></div>

        <div class="mx-auto flex w-full max-w-[1200px] translate-y-8 flex-col justify-center px-5 py-32 text-center lg:translate-y-0 lg:py-44 lg:text-left">
            <h1 class="font-display font-semibold leading-[1.02] text-white">
                <span class="block text-[42px] lg:text-[80px]">
                    <span class="font-montserrat font-semibold">E</span>xcelência
                    <span class="text-[0.5em] font-semibold">em</span>
                </span>
                <span class="block text-[31.35px] lg:text-[62px]">depilação a laser</span>
            </h1>

            <div class="text-center lg:text-left">
                <a
                    :href="WHATSAPP.agendar"
                    target="_blank"
                    rel="noopener"
                    class="mt-[24px] mb-[15px] inline-block w-[215px] rounded-[3px] bg-brand px-[15px] py-[15px] text-center font-poppins text-[14px] font-semibold uppercase leading-[19.6px] tracking-wide text-white shadow-none transition duration-200 hover:-translate-y-[2px] hover:brightness-105 hover:shadow-[0_10px_22px_rgba(41,216,219,0.28)] active:translate-y-0 lg:my-[15px] lg:w-auto lg:px-[24px] lg:text-[15px]"
                >
                    Agendar avaliação
                </a>
            </div>
        </div>
    </section>
</template>
