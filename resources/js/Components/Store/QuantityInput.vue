<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    item: { type: Object, required: true },
});

function update(quantity) {
    router.patch(`/carrinho/items/${props.item.id}`, { quantity }, {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="flex h-[38px] w-[112px] items-center overflow-hidden rounded-[3px] border border-[#d8d8d8] bg-white">
        <button type="button" class="grid h-full w-[34px] place-items-center text-[#555]" aria-label="Diminuir quantidade" @click="update(Math.max(0, item.quantity - 1))">
            <i class="fa-solid fa-minus text-[12px]"></i>
        </button>
        <input
            :value="item.quantity"
            type="number"
            min="0"
            max="99"
            class="h-full w-[44px] border-x border-[#d8d8d8] text-center font-poppins text-[15px] outline-none"
            aria-label="Quantidade"
            @change="update(Number($event.target.value || 0))"
        >
        <button type="button" class="grid h-full w-[34px] place-items-center text-[#555]" aria-label="Aumentar quantidade" @click="update(item.quantity + 1)">
            <i class="fa-solid fa-plus text-[12px]"></i>
        </button>
    </div>
</template>
