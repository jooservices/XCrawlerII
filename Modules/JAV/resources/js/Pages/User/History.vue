<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

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

    <DashboardLayout>
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h2><i class="fas fa-history"></i> My History</h2>
                    <p class="text-muted">Track your viewed and downloaded movies</p>
                </div>
            </div>

            <div v-if="history.data.length === 0" class="alert alert-info">
                <i class="fas fa-info-circle"></i> You haven't viewed or downloaded any movies yet.
            </div>

            <template v-else>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
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
                                    <Link :href="route('jav.vue.movies.show', record.jav?.uuid || record.jav?.id)" class="text-decoration-none text-dark" @click.stop>
                                        <img
                                            :src="record.jav?.cover"
                                            :alt="record.jav?.formatted_code"
                                            class="img-thumbnail me-2"
                                            style="width: 60px;"
                                            @error="(e) => { e.target.src = 'https://placehold.co/60x80?text=No+Image'; }"
                                        >
                                        {{ truncate(record.jav?.title, 50) }}
                                    </Link>
                                </td>
                                <td>
                                    <strong class="text-primary">{{ record.jav?.formatted_code }}</strong>
                                </td>
                                <td>
                                    <span v-if="record.action === 'view'" class="badge bg-info"><i class="fas fa-eye"></i> Viewed</span>
                                    <span v-else class="badge bg-success"><i class="fas fa-download"></i> Downloaded</span>
                                </td>
                                <td>{{ record.updated_at_human || record.updated_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li v-for="(link, k) in history.links" :key="k" class="page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
                                <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" />
                                <span v-else class="page-link" v-html="link.label"></span>
                            </li>
                        </ul>
                    </nav>
                </div>
            </template>
        </div>
    </DashboardLayout>
</template>
