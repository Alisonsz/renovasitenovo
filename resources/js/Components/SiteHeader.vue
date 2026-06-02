<script setup>
import { computed, ref } from 'vue';
import { NAV } from '../data/site.js';

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
        :class="isStore ? 'relative bg-brand shadow-[0_2px_8px_rgba(0,0,0,0.12)]' : 'absolute'"
    >
        <div
            class="mx-auto flex h-[68px] max-w-[1200px] items-center justify-between px-[17px] py-0 lg:px-5"
            :class="isStore ? 'lg:h-[72px] lg:py-0' : 'lg:h-auto lg:py-6'"
        >
            <a href="/" class="shrink-0">
                <img
                    src="/images/logo.png"
                    alt="Renova Laser"
                    class="mt-1 h-auto w-[174px] object-contain lg:mt-0 lg:w-auto"
                    :class="isStore ? 'lg:h-[43px]' : 'lg:h-[58px]'"
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
            <button
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

        <!-- Mobile dropdown -->
        <transition name="dropdown">
            <nav
                v-if="open"
                class="absolute left-0 top-[68px] w-full bg-[rgba(2,1,1,0.15)] py-1 text-center lg:hidden"
                aria-label="Menu mobile"
            >
                <a
                    v-for="item in mobileNav"
                    :key="item.label"
                    :href="item.href"
                    class="block px-5 py-[10px] font-sans text-[13px] font-medium uppercase tracking-[0.2px] text-white transition hover:bg-white/10"
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
