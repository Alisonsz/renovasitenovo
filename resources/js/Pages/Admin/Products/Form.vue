<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import RichTextEditor from '../../../Components/Admin/RichTextEditor.vue';
import GalleryUploader from '../../../Components/Admin/GalleryUploader.vue';

const props = defineProps({
    product: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.product?.name || '',
    slug: props.product?.slug || '',
    short_description: props.product?.short_description || '',
    description: props.product?.description || '',
    sku: props.product?.sku || '',
    regular_price: props.product?.regular_price || '0.00',
    sale_price: props.product?.sale_price || '',
    price: props.product?.price || '0.00',
    stock_status: props.product?.stock_status || 'instock',
    stock_quantity: props.product?.stock_quantity ?? null,
    manage_stock: props.product?.manage_stock ?? false,
    is_active: props.product?.is_active ?? true,
    is_custom_quote: props.product?.is_custom_quote ?? false,
    is_treatment: props.product?.is_treatment ?? false,
    sessions_count: props.product?.sessions_count ?? null,
    session_duration_min: props.product?.session_duration_min ?? 30,
    primary_category_id: props.product?.primary_category_id || '',
    category_ids: props.product?.category_ids || [],
    // Gallery items: existing images { id, url, isNew:false } + new uploads { file, url, isNew:true }.
    gallery: (props.product?.gallery || []).map((g) => ({ uid: `e${g.id}`, id: g.id, url: g.url, isNew: false })),
    merchant_visibility: props.product?.merchant_visibility || 'sync-and-show',
    merchant_brand: props.product?.merchant_brand || 'Renova Laser Depilação',
    merchant_condition: props.product?.merchant_condition || 'new',
    merchant_age_group: props.product?.merchant_age_group || 'adult',
    merchant_gender: props.product?.merchant_gender || '',
    merchant_color: props.product?.merchant_color || '',
    merchant_size: props.product?.merchant_size || '',
    merchant_is_bundle: props.product?.merchant_is_bundle ?? false,
});

// Surface any gallery-related validation error (keyed as new_images.* / image_order.*).
const galleryError = computed(() => {
    const entry = Object.entries(form.errors).find(([key]) => key.startsWith('new_images') || key.startsWith('image_order'));

    return entry ? entry[1] : '';
});

function submit() {
    const target = props.product ? `/admin/products/${props.product.id}` : '/admin/products';

    form
        .transform((data) => {
            const newImages = [];
            const order = [];
            (data.gallery || []).forEach((g) => {
                if (g.isNew && g.file) {
                    order.push(`n${newImages.length}`);
                    newImages.push(g.file);
                } else if (g.id) {
                    order.push(`e${g.id}`);
                }
            });

            const payload = {
                name: data.name,
                slug: data.slug || '',
                short_description: data.short_description || '',
                description: data.description || '',
                sku: data.sku || '',
                price: data.price || '0',
                stock_status: data.stock_status,
                manage_stock: data.manage_stock ? 1 : 0,
                is_active: data.is_active ? 1 : 0,
                is_custom_quote: data.is_custom_quote ? 1 : 0,
                is_treatment: data.is_treatment ? 1 : 0,
                session_duration_min: data.session_duration_min || 30,
                primary_category_id: data.primary_category_id || '',
                merchant_visibility: data.merchant_visibility || 'sync-and-show',
                merchant_brand: data.merchant_brand || '',
                merchant_condition: data.merchant_condition || '',
                merchant_age_group: data.merchant_age_group || '',
                merchant_gender: data.merchant_gender || '',
                merchant_color: data.merchant_color || '',
                merchant_size: data.merchant_size || '',
                merchant_is_bundle: data.merchant_is_bundle ? 1 : 0,
                gallery_set: 1,
                image_order: order,
                new_images: newImages,
            };

            // Nullable numerics: only send when actually filled (avoids 'numeric' failing on '').
            if (data.regular_price !== '' && data.regular_price != null) payload.regular_price = data.regular_price;
            if (data.sale_price !== '' && data.sale_price != null) payload.sale_price = data.sale_price;
            if (data.is_treatment && data.sessions_count) payload.sessions_count = data.sessions_count;
            if (data.manage_stock && data.stock_quantity) payload.stock_quantity = data.stock_quantity;
            payload.category_ids = data.category_ids || [];
            if (props.product) payload._method = 'put';

            return payload;
        })
        .post(target, { forceFormData: true, preserveScroll: true });
}

function destroyProduct() {
    if (!props.product || !confirm('Excluir este produto?')) return;
    router.delete(`/admin/products/${props.product.id}`);
}
</script>

<template>
    <Head :title="product ? 'Editar produto' : 'Novo produto'" />

    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-poppins text-[30px] font-extrabold text-[#363636]">{{ product ? 'Editar produto' : 'Novo produto' }}</h1>
                <p class="mt-1 font-montserrat text-[15px] text-[#777]">Gerencie catálogo, preços e dados de Merchant.</p>
            </div>
            <a href="/admin/products" class="font-poppins text-[14px] font-semibold text-brand">Voltar</a>
        </div>

        <form class="mt-8 grid gap-6 rounded-[6px] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.08)] lg:grid-cols-2" @submit.prevent="submit">
            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                Nome
                <input v-model="form.name" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                <span v-if="form.errors.name" class="text-[12px] text-red-600">{{ form.errors.name }}</span>
            </label>
            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                Slug
                <input v-model="form.slug" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
            </label>

            <label class="block font-montserrat text-[14px] font-semibold text-[#555] lg:col-span-2">
                Descrição curta
                <textarea v-model="form.short_description" rows="3" class="mt-2 w-full rounded border border-[#dde6e6] px-3 py-2 outline-none focus:border-brand"></textarea>
            </label>

            <div class="block font-montserrat text-[14px] font-semibold text-[#555] lg:col-span-2">
                Descrição
                <RichTextEditor v-model="form.description" class="mt-2" min-height="260px" placeholder="Descreva o produto…" />
            </div>

            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                Preço atual
                <input v-model="form.price" type="number" step="0.01" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
            </label>
            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                Preço regular
                <input v-model="form.regular_price" type="number" step="0.01" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
            </label>
            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                Preço promocional
                <input v-model="form.sale_price" type="number" step="0.01" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
            </label>
            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                SKU
                <input v-model="form.sku" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
            </label>

            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                Categoria principal
                <select v-model="form.primary_category_id" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    <option value="">Sem categoria</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                </select>
            </label>
            <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                Status de estoque
                <select v-model="form.stock_status" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                    <option value="instock">Em estoque</option>
                    <option value="outofstock">Sem estoque</option>
                </select>
            </label>

            <div class="lg:col-span-2">
                <p class="font-montserrat text-[14px] font-semibold text-[#555]">Galeria de imagens</p>
                <p class="mt-1 font-montserrat text-[12px] font-normal text-[#999]">A primeira imagem é a capa. Arraste para reordenar ou use os botões ao passar o mouse.</p>
                <div class="mt-3">
                    <GalleryUploader v-model="form.gallery" :error="galleryError" />
                </div>
            </div>

            <div class="lg:col-span-2">
                <p class="font-montserrat text-[14px] font-semibold text-[#555]">Categorias</p>
                <div class="mt-2 flex flex-wrap gap-3">
                    <label v-for="category in categories" :key="category.id" class="flex items-center gap-2 rounded-full bg-[#eefafa] px-3 py-2 font-montserrat text-[13px]">
                        <input v-model="form.category_ids" type="checkbox" :value="category.id" class="accent-brand">
                        {{ category.name }}
                    </label>
                </div>
            </div>

            <div class="flex flex-wrap gap-4 lg:col-span-2">
                <label class="flex items-center gap-2 font-montserrat text-[14px] font-semibold text-[#555]">
                    <input v-model="form.is_active" type="checkbox" class="accent-brand">
                    Ativo
                </label>
                <label class="flex items-center gap-2 font-montserrat text-[14px] font-semibold text-[#555]">
                    <input v-model="form.is_custom_quote" type="checkbox" class="accent-brand">
                    Orçamento personalizado
                </label>
                <label class="flex items-center gap-2 font-montserrat text-[14px] font-semibold text-[#555]">
                    <input v-model="form.manage_stock" type="checkbox" class="accent-brand">
                    Controlar estoque
                </label>
            </div>

            <label v-if="form.manage_stock" class="block font-montserrat text-[14px] font-semibold text-[#555] lg:col-span-2">
                Quantidade em estoque
                <input v-model="form.stock_quantity" type="number" min="0" class="mt-2 h-[44px] w-full max-w-[220px] rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
            </label>

            <fieldset class="grid gap-5 rounded-[6px] border border-[#e7eeee] p-5 lg:col-span-2 lg:grid-cols-3">
                <legend class="px-2 font-poppins text-[15px] font-bold text-[#363636]">Tratamento (sessões)</legend>
                <label class="flex items-center gap-2 font-montserrat text-[14px] font-semibold text-[#555] lg:col-span-3">
                    <input v-model="form.is_treatment" type="checkbox" class="accent-brand">
                    Este produto é um pacote de sessões (depilação a laser)
                </label>
                <label v-if="form.is_treatment" class="block font-montserrat text-[14px] font-semibold text-[#555]">
                    Nº de sessões
                    <input v-model="form.sessions_count" type="number" min="1" placeholder="ex.: 10" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                </label>
                <label v-if="form.is_treatment" class="block font-montserrat text-[14px] font-semibold text-[#555]">
                    Duração da sessão (min)
                    <input v-model="form.session_duration_min" type="number" min="15" step="15" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                </label>
            </fieldset>

            <fieldset class="grid gap-5 rounded-[6px] border border-[#e7eeee] p-5 lg:col-span-2 lg:grid-cols-3">
                <legend class="px-2 font-poppins text-[15px] font-bold text-[#363636]">Google Merchant</legend>
                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                    Visibilidade
                    <select v-model="form.merchant_visibility" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        <option value="sync-and-show">sync-and-show</option>
                        <option value="sync-and-hide">sync-and-hide</option>
                        <option value="dont-sync-and-show">dont-sync-and-show</option>
                    </select>
                </label>
                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                    Marca
                    <input v-model="form.merchant_brand" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                </label>
                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                    Condição
                    <select v-model="form.merchant_condition" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        <option value="">Padrão</option>
                        <option value="new">new</option>
                        <option value="used">used</option>
                        <option value="refurbished">refurbished</option>
                    </select>
                </label>
                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                    Faixa etária
                    <select v-model="form.merchant_age_group" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        <option value="">Padrão</option>
                        <option value="adult">adult</option>
                        <option value="teen">teen</option>
                        <option value="kids">kids</option>
                    </select>
                </label>
                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                    Gênero
                    <select v-model="form.merchant_gender" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                        <option value="">Padrão</option>
                        <option value="female">female</option>
                        <option value="male">male</option>
                        <option value="unisex">unisex</option>
                    </select>
                </label>
                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                    Cor
                    <input v-model="form.merchant_color" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                </label>
                <label class="block font-montserrat text-[14px] font-semibold text-[#555]">
                    Tamanho
                    <input v-model="form.merchant_size" class="mt-2 h-[44px] w-full rounded border border-[#dde6e6] px-3 outline-none focus:border-brand">
                </label>
                <label class="mt-8 flex items-center gap-2 font-montserrat text-[14px] font-semibold text-[#555]">
                    <input v-model="form.merchant_is_bundle" type="checkbox" class="accent-brand">
                    Produto é bundle
                </label>
            </fieldset>

            <div class="lg:col-span-2">
                <div class="flex items-center gap-3">
                    <button class="rounded bg-brand px-6 py-3 font-poppins text-[14px] font-semibold text-white transition hover:brightness-105 disabled:opacity-60" :disabled="form.processing">
                        <i v-if="form.processing" class="fa-solid fa-spinner fa-spin mr-1"></i>
                        {{ form.processing ? 'Salvando...' : 'Salvar produto' }}
                    </button>
                    <button v-if="product" type="button" class="rounded border border-red-200 px-6 py-3 font-poppins text-[14px] font-semibold text-red-600" @click="destroyProduct">
                        Excluir
                    </button>
                </div>
                <div v-if="form.progress" class="mt-3 h-2 w-full max-w-[320px] overflow-hidden rounded-full bg-[#e6f4f4]">
                    <div class="h-full rounded-full bg-brand transition-all" :style="{ width: `${form.progress.percentage}%` }"></div>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>
