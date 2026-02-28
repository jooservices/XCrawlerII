import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const toAbsoluteUrl = (value) => {
    try {
        return new globalThis.URL(value, window.location.origin).toString();
    } catch {
        return window.location.href;
    }
};

export function useSeo(input = {}) {
    const page = usePage();

    const resolved = computed(() => {
        const defaults = page.props.seo;

        const titleBase = input.title || defaults?.defaultTitle || 'XCrawler';
        const titleSuffix = defaults?.titleSuffix ? ` - ${defaults.titleSuffix}` : '';
        const title = input.title ? `${titleBase}${titleSuffix}` : titleBase;
        const description = input.description || defaults?.defaultDescription || '';
        const canonical = toAbsoluteUrl(input.canonical || window.location.pathname);
        const isIndexable = input.indexable ?? true;
        const robots = input.robots || (isIndexable ? defaults?.defaultRobotsPublic : defaults?.defaultRobotsPrivate) || 'index,follow';
        const ogImage = toAbsoluteUrl(input.image || defaults?.defaultOgImage || '/images/og-default.png');
        const ogType = input.type || 'website';

        return {
            title,
            description,
            canonical,
            robots,
            ogTitle: title,
            ogDescription: description,
            ogType,
            ogUrl: canonical,
            ogImage,
            ogSiteName: defaults?.siteName || 'XCrawler',
            twitterCard: defaults?.twitterCard || 'summary_large_image',
            twitterTitle: title,
            twitterDescription: description,
            twitterImage: ogImage,
            twitterSite: defaults?.twitterSite || '',
            jsonLd: input.jsonLd ? JSON.stringify(input.jsonLd) : null,
        };
    });

    return {
        seo: resolved,
    };
}
