<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    profile: { type: Object, required: true },
});

const accountForm = useForm({
    name: props.profile.name,
    email: props.profile.email,
    current_password: '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function saveAccount() {
    accountForm.put('/admin/minha-conta', {
        preserveScroll: true,
        onSuccess: () => accountForm.reset('current_password'),
    });
}

function savePassword() {
    passwordForm.put('/admin/minha-conta/senha', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <Head title="Minha conta" />
    <AdminLayout>
        <div>
            <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">Minha conta</h1>
            <p class="mt-1 font-montserrat text-[15px] text-[#777]">Atualize seus dados de acesso ao painel.</p>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <!-- Dados da conta -->
            <form class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]" @submit.prevent="saveAccount">
                <h2 class="font-poppins text-[17px] font-bold text-[#333]">Dados da conta</h2>
                <p class="mt-1 font-montserrat text-[13px] text-[#888]">Para alterar, confirme com sua senha atual.</p>

                <div class="mt-5 space-y-4">
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        Nome
                        <input v-model="accountForm.name" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        <span v-if="accountForm.errors.name" class="text-[12px] text-red-600">{{ accountForm.errors.name }}</span>
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        E-mail
                        <input v-model="accountForm.email" type="email" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        <span v-if="accountForm.errors.email" class="text-[12px] text-red-600">{{ accountForm.errors.email }}</span>
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        Senha atual
                        <input v-model="accountForm.current_password" type="password" autocomplete="current-password" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        <span v-if="accountForm.errors.current_password" class="text-[12px] text-red-600">{{ accountForm.errors.current_password }}</span>
                    </label>
                </div>

                <button :disabled="accountForm.processing" class="mt-6 h-[46px] rounded bg-brand px-7 font-poppins text-[15px] font-semibold text-white transition hover:brightness-105 disabled:opacity-60">
                    Salvar dados
                </button>
            </form>

            <!-- Trocar senha -->
            <form class="rounded-[6px] bg-white p-6 shadow-[0_0_10px_rgba(0,0,0,0.08)]" @submit.prevent="savePassword">
                <h2 class="font-poppins text-[17px] font-bold text-[#333]">Alterar senha</h2>
                <p class="mt-1 font-montserrat text-[13px] text-[#888]">Mínimo de 8 caracteres.</p>

                <div class="mt-5 space-y-4">
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        Senha atual
                        <input v-model="passwordForm.current_password" type="password" autocomplete="current-password" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        <span v-if="passwordForm.errors.current_password" class="text-[12px] text-red-600">{{ passwordForm.errors.current_password }}</span>
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        Nova senha
                        <input v-model="passwordForm.password" type="password" autocomplete="new-password" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        <span v-if="passwordForm.errors.password" class="text-[12px] text-red-600">{{ passwordForm.errors.password }}</span>
                    </label>
                    <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                        Confirmar nova senha
                        <input v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    </label>
                </div>

                <button :disabled="passwordForm.processing" class="mt-6 h-[46px] rounded bg-brand px-7 font-poppins text-[15px] font-semibold text-white transition hover:brightness-105 disabled:opacity-60">
                    Alterar senha
                </button>
            </form>
        </div>
    </AdminLayout>
</template>
