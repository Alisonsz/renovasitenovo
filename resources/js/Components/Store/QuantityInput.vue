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
    <div class="flex h-[40px] w-[118px] items-center overflow-hidden rounded-[4px] border border-[#d8e3e3] bg-white shadow-sm">
        <button type="button" class="grid h-full w-[36px] place-items-center text-[#555] transition hover:bg-[#edfafa] hover:text-brand" aria-label="Diminuir quantidade" @click="update(Math.max(0, item.quantity - 1))">
            <i class="fa-solid fa-minus text-[12px]"></i>
        </button>
        <input
            :value="item.quantity"
            type="number"
            min="0"
            max="99"
            class="h-full w-[46px] border-x border-[#d8e3e3] text-center font-poppins text-[15px] outline-none"
            aria-label="Quantidade"
            @change="update(Number($event.target.value || 0))"
        >
        <button type="button" class="grid h-full w-[36px] place-items-center text-[#555] transition hover:bg-[#edfafa] hover:text-brand" aria-label="Aumentar quantidade" @click="update(item.quantity + 1)">
            <i class="fa-solid fa-plus text-[12px]"></i>
        </button>
    </div>
</template>
