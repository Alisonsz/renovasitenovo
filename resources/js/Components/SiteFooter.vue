<script setup>
import { FOOTER, SOCIAL } from '../data/site.js';

const hiddenOnMobileColumns = new Set(['Ajuda']);

const linkTarget = (href) => (href?.startsWith('http') ? '_blank' : null);
const linkRel = (href) => (href?.startsWith('http') ? 'noopener' : null);
const isPolicyHiddenOnMobile = (label) => label.includes('Cancelamento');
</script>

<template>
    <footer class="bg-[linear-gradient(180deg,#FFFFFF_0%,#DBE8E9_100%)] text-ink">
        <div class="mx-auto max-w-[1140px] px-5 py-5 lg:py-12">
            <div class="grid grid-cols-2 gap-x-3 gap-y-4 text-center md:text-left lg:grid-cols-[250px_148px_148px_180px_1fr] lg:items-start lg:gap-x-[60px]">
                <div class="col-span-2 flex justify-center md:justify-start lg:col-span-1">
                    <img src="/images/logo-footer.png" alt="Renova Laser" class="h-[46px] w-[211px] object-fill lg:h-auto lg:w-[160px]" />
                </div>

                <div
                    v-for="col in FOOTER.columns"
                    :key="col.title"
                    :class="{ 'hidden lg:block': hiddenOnMobileColumns.has(col.title) }"
                >
                    <h4 class="text-[13px] font-semibold text-heading lg:text-[16px]">{{ col.title }}</h4>
                    <ul class="mt-1 space-y-0.5 lg:mt-3 lg:space-y-2">
                        <li v-for="l in col.links" :key="l.label">
                            <a
                                :href="l.href"
                                :target="linkTarget(l.href)"
                                :rel="linkRel(l.href)"
                                class="text-[12px] leading-tight text-muted transition hover:text-brand-dark lg:whitespace-nowrap lg:text-[15px] lg:leading-normal"
                            >{{ l.label }}</a>
                        </li>
                    </ul>
                </div>

                <div class="col-span-2 lg:col-span-1 lg:justify-self-end">
                    <h4 class="font-montserrat text-[13px] font-bold text-heading lg:text-[16px]">Siga a gente</h4>
                    <div class="mt-3 flex justify-center gap-2 md:justify-start">
                        <a
                            :href="SOCIAL.instagram"
                            target="_blank"
                            rel="noopener"
                            aria-label="Instagram"
                            class="grid h-[26px] w-[26px] place-items-center rounded-full bg-brand text-white transition hover:brightness-105"
                        >
                            <i class="fa-brands fa-instagram text-[15px]"></i>
                        </a>
                        <a
                            :href="SOCIAL.tiktok"
                            target="_blank"
                            rel="noopener"
                            aria-label="TikTok"
                            class="grid h-[26px] w-[26px] place-items-center rounded-full bg-brand text-white transition hover:brightness-105"
                        >
                            <i class="fa-brands fa-tiktok text-[15px]"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap justify-center gap-x-4 gap-y-1 lg:mt-10 lg:gap-x-6 lg:gap-y-2">
                <a
                    v-for="p in FOOTER.policies"
                    :key="p.label"
                    :href="p.href"
                    :target="linkTarget(p.href)"
                    :rel="linkRel(p.href)"
                    :class="{ 'hidden lg:block': isPolicyHiddenOnMobile(p.label) }"
                    class="text-center text-[11px] text-[#8a9394] transition hover:text-brand-dark lg:text-[13px]"
                >
                    {{ p.label }}
                </a>
            </div>

            <div class="mt-4 flex flex-col gap-1 text-center font-montserrat text-[11px] italic text-muted sm:flex-row sm:items-center sm:justify-between sm:text-left lg:mt-6 lg:gap-2 lg:text-[13px]">
                <p>
                    &copy; 2025 Renova Laser | Todos os direitos reservados. Feito com
                    <i class="fa-solid fa-heart mx-1 text-[#cc3366]" aria-hidden="true"></i>
                    no Tatuapé
                </p>
                <p class="hidden lg:block">
                    <i class="fa-solid fa-lock mr-1 text-[#f6b83f]" aria-hidden="true"></i>
                    Este é um site seguro. Todos os dados são criptografados com SSL.
                </p>
            </div>
        </div>
    </footer>
</template>
