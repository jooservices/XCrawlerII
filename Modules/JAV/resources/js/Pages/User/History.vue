<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';
import EmptyState from '@jav/Components/UI/EmptyState.vue';
import DataTableShell from '@jav/Components/UI/DataTableShell.vue';

const props = defineProps({
    history: Object,
});

const truncate = (value, max = 50) => {
    const text = String(value || '');
    if (text.length <= max) {
        return text;
    }

    return `${text.slice(0, max)}...`;
};
</script>

<template>
    <Head title="History" />

    <PageShell>
        <template #header>
            <SectionHeader title="History" subtitle="Track your viewed and downloaded movies" />
        </template>

        <EmptyState
            v-if="history.data.length === 0"
            tone="info"
            icon="fas fa-history"
            message="You haven't viewed or downloaded any movies yet."
        />

        <template v-else>
            <DataTableShell title="Recent Activity">
                <div class="ui-table-responsive">
                    <table class="ui-table ui-table-striped ui-table-hover">
                        <thead>
                            <tr>
                                <th>Movie</th>
                                <th>Code</th>
                                <th>Action</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="record in history.data"
                                :key="record.id"
                                class="history-row u-cursor-pointer"
                                @click="router.visit(route('jav.vue.movies.show', record.jav?.uuid || record.jav?.id))"
                            >
                                <td>
                                    <Link :href="route('jav.vue.movies.show', record.jav?.uuid || record.jav?.id)" class="u-no-underline u-text-dark" @click.stop>
                                        <img
                                            :src="record.jav?.cover"
                                            :alt="record.jav?.formatted_code"
                                            class="img-thumbnail mr-2 u-w-60"
                                            @error="(e) => { e.target.src = 'https://placehold.co/60x80?text=No+Image'; }"
                                        >
                                        {{ truncate(record.jav?.title, 50) }}
                                    </Link>
                                </td>
                                <td>
                                    <strong class="u-text-primary">{{ record.jav?.formatted_code || record.jav?.code || '-' }}</strong>
                                </td>
                                <td>
                                    <span v-if="record.action === 'view'" class="ui-badge u-bg-info"><i class="fas fa-eye"></i> Viewed</span>
                                    <span v-else class="ui-badge u-bg-success"><i class="fas fa-download"></i> Downloaded</span>
                                </td>
                                <td>{{ record.updated_at_human || record.updated_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </DataTableShell>

            <div class="u-flex u-justify-center mt-4">
                <nav aria-label="Page navigation">
                    <ul class="ui-pagination">
                        <li v-for="(link, k) in history.links" :key="k" class="ui-page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
                            <Link v-if="link.url" class="ui-page-link" :href="link.url" v-html="link.label" />
                            <span v-else class="ui-page-link" v-html="link.label"></span>
                        </li>
                    </ul>
                </nav>
            </div>
        </template>
    </PageShell>
</template>
