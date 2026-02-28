<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useSeo } from '../../composables/useSeo';

const props = defineProps({
    seo: {
        type: Object,
        default: () => ({}),
    },
});

const { seo } = useSeo(props.seo || {});
const jsonLdPayload = computed(() => seo.value.jsonLd);
</script>

<template>
    <Head>
        <title>{{ seo.title }}</title>

        <meta name="description" :content="seo.description">
        <meta name="robots" :content="seo.robots">
        <link rel="canonical" :href="seo.canonical">

        <meta property="og:title" :content="seo.ogTitle">
        <meta property="og:description" :content="seo.ogDescription">
        <meta property="og:type" :content="seo.ogType">
        <meta property="og:url" :content="seo.ogUrl">
        <meta property="og:image" :content="seo.ogImage">
        <meta property="og:site_name" :content="seo.ogSiteName">

        <meta name="twitter:card" :content="seo.twitterCard">
        <meta name="twitter:title" :content="seo.twitterTitle">
        <meta name="twitter:description" :content="seo.twitterDescription">
        <meta name="twitter:image" :content="seo.twitterImage">
        <meta v-if="seo.twitterSite" name="twitter:site" :content="seo.twitterSite">

        <script v-if="jsonLdPayload" type="application/ld+json">{{ jsonLdPayload }}</script>
    </Head>
</template>
