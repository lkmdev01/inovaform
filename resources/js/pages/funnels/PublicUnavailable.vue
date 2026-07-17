<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type DesignSettings = {
    pageColor: string;
    cardColor: string;
    headingColor: string;
    textColor: string;
    accentColor: string;
    logoUrl?: string;
    faviconUrl?: string;
};

const props = defineProps<{
    statusCode: number;
    title: string;
    description: string;
    homeUrl: string;
    design: DesignSettings;
    funnel: {
        name: string;
        slug: string;
        custom_domain: string | null;
    } | null;
}>();

const logoAssetUrl = computed(() => {
    const value = (props.design.logoUrl ?? '').trim();

    return value !== '' ? value : '';
});

const faviconAssetUrl = computed(() => {
    const value = (props.design.faviconUrl ?? '').trim();

    return value !== '' ? value : '';
});
</script>

<template>
    <Head :title="props.title">
        <meta name="robots" content="noindex,nofollow" />
        <meta name="description" :content="props.description" />
        <link v-if="faviconAssetUrl" rel="icon" :href="faviconAssetUrl" />
    </Head>

    <div class="flex min-h-screen items-center justify-center px-4 py-12" :style="{ backgroundColor: props.design.pageColor }">
        <div
            class="w-full max-w-xl rounded-[2rem] border px-8 py-10 text-center shadow-[0_26px_60px_rgba(0,0,0,0.38)]"
            :style="{ backgroundColor: props.design.cardColor, borderColor: `${props.design.accentColor}66` }"
        >
            <div class="mb-6 flex flex-col items-center gap-3">
                <img v-if="logoAssetUrl" :src="logoAssetUrl" alt="Logo do funil" class="h-10 w-auto max-w-40 object-contain" />
                <div v-else class="text-xs uppercase tracking-[0.2em]" :style="{ color: props.design.textColor }">
                    {{ props.funnel?.name ?? 'Inovaform' }}
                </div>
                <div class="rounded-full px-3 py-1 text-xs font-semibold" :style="{ backgroundColor: `${props.design.accentColor}1F`, color: props.design.headingColor }">
                    Status {{ props.statusCode }}
                </div>
            </div>

            <h1 class="text-3xl font-semibold" :style="{ color: props.design.headingColor }">
                {{ props.title }}
            </h1>
            <p class="mt-3 text-base leading-7" :style="{ color: props.design.textColor }">
                {{ props.description }}
            </p>

            <div class="mt-8 flex justify-center">
                <Link
                    :href="props.homeUrl"
                    class="rounded-xl px-5 py-3 text-sm font-semibold text-white"
                    :style="{ backgroundColor: props.design.accentColor }"
                >
                    Voltar para o inicio
                </Link>
            </div>
        </div>
    </div>
</template>
