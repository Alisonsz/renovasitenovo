<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const nav = [
    { label: 'Dashboard', href: '/admin', icon: 'fa-chart-line' },
    { section: 'Clínica' },
    { label: 'Agenda', href: '/admin/appointments', icon: 'fa-calendar-days' },
    { label: 'Clientes', href: '/admin/customers', icon: 'fa-users' },
    { label: 'Profissionais', href: '/admin/professionals', icon: 'fa-user-nurse' },
    { section: 'Loja' },
    { label: 'Pedidos', href: '/admin/orders', icon: 'fa-receipt' },
    { label: 'Produtos', href: '/admin/products', icon: 'fa-box' },
    { label: 'Categorias', href: '/admin/categories', icon: 'fa-layer-group' },
    { label: 'Cupons', href: '/admin/coupons', icon: 'fa-ticket' },
    { section: 'Conteúdo & gestão' },
    { label: 'Blog', href: '/admin/blog-posts', icon: 'fa-newspaper' },
    { label: 'Relatórios', href: '/admin/reports', icon: 'fa-chart-pie' },
    { label: 'Usuários', href: '/admin/usuarios', icon: 'fa-user-shield' },
    { label: 'Configurações', href: '/admin/settings', icon: 'fa-gear' },
];

// active state: exact for dashboard, prefix for sections
function isActive(href) {
    if (href === '/admin') return page.url === '/admin';
    return page.url.startsWith(href);
}

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
            <nav class="mt-8 space-y-1">
                <template v-for="(item, i) in nav" :key="i">
                    <p v-if="item.section" class="px-3 pb-1 pt-4 font-poppins text-[11px] font-bold uppercase tracking-wide text-[#9aa]">{{ item.section }}</p>
                    <Link
                        v-else
                        :href="item.href"
                        class="flex h-[40px] items-center gap-3 rounded-[5px] px-3 font-poppins text-[14px] font-semibold text-[#555] transition hover:bg-[#e8f8f8] hover:text-brand"
                        :class="{ 'bg-[#e8f8f8] text-brand': isActive(item.href) }"
                    >
                        <i :class="`fa-solid ${item.icon}`" class="w-5 text-center"></i>
                        {{ item.label }}
                    </Link>
                </template>
            </nav>
        </aside>

        <div class="lg:pl-[250px]">
            <header class="sticky top-0 z-20 flex h-[68px] items-center justify-between border-b border-[#e5ecec] bg-white/95 px-5 backdrop-blur lg:px-8">
                <div>
                    <p class="font-poppins text-[13px] font-semibold uppercase text-brand">Renova Laser</p>
                    <p class="font-montserrat text-[13px] text-[#777]">Painel administrativo</p>
                </div>
                <div class="flex items-center gap-4">
                    <Link href="/admin/minha-conta" class="hidden items-center gap-2 font-montserrat text-[14px] text-[#555] transition hover:text-brand sm:flex">
                        <i class="fa-solid fa-circle-user text-[18px]"></i>{{ user?.name }}
                    </Link>
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
                <div v-if="page.props.flash?.success" class="mb-6 rounded-[5px] border border-green-200 bg-green-50 px-4 py-3 font-montserrat text-[14px] text-green-800">
                    {{ page.props.flash.success }}
                </div>
                <div v-if="page.props.flash?.error" class="mb-6 rounded-[5px] border border-red-200 bg-red-50 px-4 py-3 font-montserrat text-[14px] text-red-800">
                    {{ page.props.flash.error }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
