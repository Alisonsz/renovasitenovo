<script setup>
import { computed, ref } from 'vue';
import { NAV, WHATSAPP } from '../data/site.js';

const props = defineProps({
    variant: { type: String, default: 'default' },
});

const open = ref(false);
const isStore = computed(() => props.variant === 'store');
const mobileNav = [
    { label: 'Nossa Tecnologia', href: NAV.primary[1].href },
    { label: 'Quem Somos', href: NAV.primary[0].href },
    ...NAV.secondary,
];
</script>

<template>
    <header
        class="inset-x-0 top-0 z-40"
        :class="isStore ? 'relative bg-white shadow-[0_2px_8px_rgba(0,0,0,0.12)] lg:bg-brand' : 'absolute'"
    >
        <div
            class="mx-auto flex max-w-[1200px] items-center justify-between px-[17px] py-0 lg:px-5"
            :class="isStore ? 'h-[56px] lg:h-[72px] lg:py-0' : 'h-[68px] lg:h-auto lg:py-6'"
        >
            <button
                v-if="isStore"
                class="-ml-1 grid h-[42px] w-[42px] place-items-center rounded-[3px] bg-transparent text-brand lg:hidden"
                :aria-label="open ? 'Fechar menu' : 'Abrir menu'"
                :aria-expanded="open"
                @click="open = !open"
            >
                <i v-if="open" class="fa-solid fa-xmark text-[24px]"></i>
                <span v-else class="flex flex-col gap-[5px]" aria-hidden="true">
                    <span class="block h-[3px] w-[20px] rounded-full bg-current"></span>
                    <span class="block h-[3px] w-[20px] rounded-full bg-current"></span>
                    <span class="block h-[3px] w-[20px] rounded-full bg-current"></span>
                </span>
            </button>

            <a href="/" class="shrink-0" :class="{ 'absolute left-1/2 -translate-x-1/2 lg:static lg:translate-x-0': isStore }">
                <img
                    src="/images/logo.png"
                    alt="Renova Laser"
                    class="mt-1 h-auto object-contain lg:mt-0 lg:w-auto"
                    :class="isStore ? 'w-[174px] lg:h-[43px]' : 'w-[174px] lg:h-[58px]'"
                />
            </a>

            <!-- Desktop nav -->
            <nav class="hidden items-center lg:flex" :class="isStore ? 'gap-5' : 'gap-8'">
                <form v-if="isStore" class="relative w-[285px]" role="search">
                    <input
                        type="search"
                        aria-label="Buscar produtos"
                        placeholder="Pesquisar produtos..."
                        class="h-[38px] w-full rounded-[3px] border-0 bg-white px-4 pr-10 font-sans text-[13px] text-[#656565] outline-none placeholder:text-[#9b9b9b]"
                    >
                    <i class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-[15px] text-brand"></i>
                </form>
                <a
                    v-for="item in NAV.primary"
                    :key="item.href"
                    :href="item.href"
                    class="font-sans font-medium text-white/95 transition hover:text-white"
                    :class="isStore ? 'text-[14px]' : 'text-[17px]'"
                >
                    {{ item.label }}
                </a>
                <a
                    :href="NAV.account.href"
                    class="flex items-center gap-2 font-sans text-white"
                    :class="isStore ? 'text-[13px]' : 'text-[17px]'"
                >
                    <i class="fa-solid fa-circle-user text-white" :class="isStore ? 'text-[22px]' : 'text-[28px]'"></i>
                    <span class="leading-tight">
                        <span class="block">{{ NAV.account.line1 }}</span>
                        <span class="block">
                            <span class="text-[#4A4A4A]">{{ NAV.account.or }}</span>
                            {{ NAV.account.post }}
                        </span>
                    </span>
                </a>
                <a v-if="isStore" href="/carrinho" class="relative grid h-[38px] w-[38px] place-items-center text-white" aria-label="Carrinho">
                    <i class="fa-solid fa-cart-shopping text-[22px]"></i>
                </a>
            </nav>

            <!-- Mobile hamburger -->
            <a
                v-if="isStore"
                :href="WHATSAPP.vendas"
                target="_blank"
                rel="noopener"
                class="-mr-1 grid h-[42px] w-[42px] place-items-center rounded-[3px] text-brand lg:hidden"
                aria-label="WhatsApp"
            >
                <i class="fa-brands fa-whatsapp text-[25px]"></i>
            </a>

            <button
                v-else
                class="-mr-1 flex h-[46px] w-[38px] items-center justify-center rounded-[3px] bg-transparent text-white lg:hidden"
                :aria-label="open ? 'Fechar menu' : 'Abrir menu'"
                :aria-expanded="open"
                @click="open = !open"
            >
                <i v-if="open" class="fa-solid fa-xmark text-[25px]"></i>
                <span v-else class="flex flex-col gap-[5px]" aria-hidden="true">
                    <span class="block h-[2px] w-[27px] rounded-full bg-current"></span>
                    <span class="block h-[2px] w-[27px] rounded-full bg-current"></span>
                    <span class="block h-[2px] w-[27px] rounded-full bg-current"></span>
                </span>
            </button>
        </div>

        <form v-if="isStore" class="mx-auto px-[22px] pb-[14px] lg:hidden" role="search">
            <label class="relative block">
                <span class="sr-only">Buscar pacotes</span>
                <i class="fa-solid fa-magnifying-glass absolute left-[17px] top-1/2 -translate-y-1/2 text-[18px] text-[#111]"></i>
                <input
                    type="search"
                    aria-label="Buscar pacotes"
                    placeholder="Buscar pacotes"
                    class="h-[41px] w-full rounded-full border border-[#d1d1d1] bg-white pl-[48px] pr-4 font-montserrat text-[14px] text-[#666] shadow-[0_0_4px_rgba(0,0,0,0.22)] outline-none placeholder:text-[#8a8a8a] focus:border-brand focus:ring-2 focus:ring-brand/20"
                >
            </label>
        </form>

        <!-- Mobile dropdown -->
        <transition name="dropdown">
            <nav
                v-if="open"
                class="absolute left-0 w-full py-1 text-center lg:hidden"
                :class="isStore ? 'top-[111px] bg-white shadow-[0_8px_18px_rgba(0,0,0,0.12)]' : 'top-[68px] bg-[rgba(2,1,1,0.15)]'"
                aria-label="Menu mobile"
            >
                <a
                    v-for="item in mobileNav"
                    :key="item.label"
                    :href="item.href"
                    class="block px-5 py-[10px] font-sans text-[13px] font-medium uppercase tracking-[0.2px] transition"
                    :class="isStore ? 'text-[#555] hover:bg-[#eefafa] hover:text-brand' : 'text-white hover:bg-white/10'"
                    @click="open = false"
                >
                    {{ item.label }}
                </a>
            </nav>
        </transition>
    </header>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
