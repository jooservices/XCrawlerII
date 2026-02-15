<script setup>
import { Head, Link, router } from '@inertiajs/vue3';

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

    
        <div class="ui-container-fluid">
            <div class="ui-row mb-4">
                <div class="ui-col-12">
                    <h2><i class="fas fa-history"></i> My History</h2>
                    <p class="u-text-muted">Track your viewed and downloaded movies</p>
                </div>
            </div>

            <div v-if="history.data.length === 0" class="ui-alert ui-alert-info">
                <i class="fas fa-info-circle"></i> You haven't viewed or downloaded any movies yet.
            </div>

            <template v-else>
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
                                class="history-row"
                                style="cursor: pointer;"
                                @click="router.visit(route('jav.vue.movies.show', record.jav?.uuid || record.jav?.id))"
                            >
                                <td>
                                    <Link :href="route('jav.vue.movies.show', record.jav?.uuid || record.jav?.id)" class="u-no-underline u-text-dark" @click.stop>
                                        <img
                                            :src="record.jav?.cover"
                                            :alt="record.jav?.formatted_code"
                                            class="img-thumbnail mr-2"
                                            style="width: 60px;"
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
        </div>
    
</template>
