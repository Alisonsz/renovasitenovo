<script setup>
import { LOCATION } from '../data/site.js';

const mapSrc =
    'https://www.google.com/maps?q=Renova%20Laser%20Depila%C3%A7%C3%A3o%20Rua%20Cantagalo%20223%20Tatuap%C3%A9%20S%C3%A3o%20Paulo&output=embed';
</script>

<template>
    <section class="bg-white px-5 py-14 lg:py-20">
        <div class="mx-auto max-w-[1140px]">
            <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                <div class="text-center lg:text-left">
                    <h2 class="text-[30px] font-extrabold text-heading lg:text-[39px]">Onde estamos</h2>

                    <div class="mt-8 space-y-6">
                        <div>
                            <h3 class="text-[19px] font-semibold text-heading-soft">{{ LOCATION.city }}</h3>
                            <p class="mt-1 text-[16px] text-muted">{{ LOCATION.address }}</p>
                        </div>
                        <div>
                            <h3 class="text-[19px] font-semibold text-heading-soft">Horário de atendimento</h3>
                            <p v-for="h in LOCATION.hours" :key="h" class="mt-1 text-[16px] text-muted">{{ h }}</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl shadow-md">
                    <iframe
                        :src="mapSrc"
                        class="h-[320px] w-full lg:h-[380px]"
                        style="border: 0"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Mapa - Renova Laser Tatuapé"
                    ></iframe>
                </div>
            </div>
        </div>
    </section>
</template>
