<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/ovodepapagaio');
}
</script>

<template>
    <Head title="Entrar" />

    <main class="grid min-h-screen place-items-center bg-[#e8f7f7] px-5 py-10 font-sans">
        <form class="w-full max-w-[420px] rounded-[8px] bg-white p-8 shadow-[0_14px_34px_rgba(0,0,0,0.14)]" @submit.prevent="submit">
            <img src="/images/logo.png" alt="Renova Laser" class="mx-auto h-[48px] w-auto">
            <h1 class="mt-8 text-center font-poppins text-[28px] font-extrabold text-[#363636]">Acessar painel</h1>

            <label class="mt-8 block font-montserrat text-[14px] font-semibold text-[#555]">
                E-mail
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    class="mt-2 h-[45px] w-full rounded-[3px] border border-[#ddd] px-3 font-normal outline-none transition focus:border-brand"
                    autofocus
                >
                <span v-if="form.errors.email" class="mt-1 block text-[12px] text-red-600">{{ form.errors.email }}</span>
            </label>

            <label class="mt-5 block font-montserrat text-[14px] font-semibold text-[#555]">
                Senha
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    class="mt-2 h-[45px] w-full rounded-[3px] border border-[#ddd] px-3 font-normal outline-none transition focus:border-brand"
                >
                <span v-if="form.errors.password" class="mt-1 block text-[12px] text-red-600">{{ form.errors.password }}</span>
            </label>

            <label class="mt-5 flex items-center gap-2 font-montserrat text-[14px] text-[#555]">
                <input v-model="form.remember" type="checkbox" class="accent-brand">
                Manter conectado
            </label>

            <button
                type="submit"
                class="mt-7 h-[48px] w-full rounded-[3px] bg-brand px-5 font-poppins text-[16px] font-semibold text-white transition duration-200 hover:-translate-y-[2px] hover:brightness-105 hover:shadow-[0_10px_22px_rgba(41,216,219,0.25)] active:translate-y-0 disabled:opacity-60"
                :disabled="form.processing"
            >
                Entrar
            </button>
        </form>
    </main>
</template>
