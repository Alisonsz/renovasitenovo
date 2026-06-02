<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const nav = [
    { label: 'Dashboard', href: '/admin', icon: 'fa-chart-line' },
    { label: 'Produtos', href: '/admin/products', icon: 'fa-box' },
    { label: 'Categorias', href: '/admin/categories', icon: 'fa-layer-group' },
    { label: 'Blog', href: '/admin/blog-posts', icon: 'fa-newspaper' },
    { label: 'Pedidos', href: '/admin/orders', icon: 'fa-receipt' },
    { label: 'Cupons', href: '/admin/coupons', icon: 'fa-ticket' },
];

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen bg-[#f5f7f7] font-sans text-[#303030]">
        <aside class="fixed inset-y-0 left-0 hidden w-[250px] border-r border-[#e5ecec] bg-white px-5 py-6 lg:block">
            <a href="/admin" class="block">
                <img src="/images/logo.png" alt="Renova Laser" class="h-[42px] w-auto">
            </a>
            <nav class="mt-10 space-y-2">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="flex h-[42px] items-center gap-3 rounded-[5px] px-3 font-poppins text-[14px] font-semibold text-[#555] transition hover:bg-[#e8f8f8] hover:text-brand"
                    :class="{ 'bg-[#e8f8f8] text-brand': page.url === item.href }"
                >
                    <i :class="`fa-solid ${item.icon}`" class="w-5 text-center"></i>
                    {{ item.label }}
                </Link>
            </nav>
        </aside>

        <div class="lg:pl-[250px]">
            <header class="sticky top-0 z-20 flex h-[68px] items-center justify-between border-b border-[#e5ecec] bg-white/95 px-5 backdrop-blur lg:px-8">
                <div>
                    <p class="font-poppins text-[13px] font-semibold uppercase text-brand">Renova Laser</p>
                    <p class="font-montserrat text-[13px] text-[#777]">Painel administrativo</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="hidden font-montserrat text-[14px] text-[#555] sm:inline">{{ user?.name }}</span>
                    <button
                        type="button"
                        class="h-[38px] rounded-[3px] border border-[#dce6e6] px-4 font-poppins text-[13px] font-semibold text-[#555] transition hover:border-brand hover:text-brand"
                        @click="logout"
                    >
                        Sair
                    </button>
                </div>
            </header>

            <main class="px-5 py-8 lg:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>
