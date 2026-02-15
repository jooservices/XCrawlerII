<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    actorInsights: {
        type: Object,
        default: null,
    },
    title: {
        type: String,
        default: 'Actor Analytics',
    },
    emptyMessage: {
        type: String,
        default: 'No analytics data available.',
    },
    showTitle: {
        type: Boolean,
        default: true,
    },
    showActorGenres: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div>
        <h5 v-if="showTitle">{{ title }}</h5>

        <div v-if="actorInsights" class="ui-row ui-g-3">
            <div class="ui-col-lg-6">
                <div class="ui-card">
                    <div class="ui-card-body">
                        <h6 class="mb-2">
                            Predicted Genres
                            <i
                                class="fas fa-info-circle u-text-muted"
                                title="Prediction uses a cohort of similar actors by bio/profile. It is not only this actor's movie history."
                            ></i>
                        </h6>
                        <p class="small u-text-muted mb-2">
                            Matched actors: {{ Number(actorInsights.matched_actors || 0).toLocaleString() }}
                            <i
                                class="fas fa-info-circle"
                                title="How many similar actors were matched to build this prediction cohort."
                            ></i>
                        </p>
                        <div class="ui-table-responsive">
                            <table class="ui-table ui-table-sm ui-table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Genre</th>
                                        <th class="u-text-end">
                                            Matched Actors With Genre
                                            <i
                                                class="fas fa-info-circle"
                                                title="Count of matched actors who have this genre tag."
                                            ></i>
                                        </th>
                                        <th class="u-text-end">
                                            Cohort Score
                                            <i
                                                class="fas fa-info-circle"
                                                title="Ranking score = genre count / total counts in returned top genres."
                                            ></i>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="(actorInsights.predicted_genres || []).length === 0">
                                        <td colspan="3" class="u-text-muted">No prediction data.</td>
                                    </tr>
                                    <tr v-for="row in actorInsights.predicted_genres || []" :key="`insight-pred-${row.genre}`">
                                        <td>
                                            <Link :href="route('jav.vue.dashboard', { tag: row.genre })" class="u-no-underline">
                                                {{ row.genre }}
                                            </Link>
                                        </td>
                                        <td class="u-text-end">{{ Number(row.count || 0).toLocaleString() }}</td>
                                        <td class="u-text-end">{{ Number(row.probability || 0).toFixed(4) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ui-col-lg-6">
                <div class="ui-card">
                    <div class="ui-card-body">
                        <h6 class="mb-2">Week / Month / Year Summary</h6>
                        <div class="ui-table-responsive">
                            <table class="ui-table ui-table-sm ui-table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Window</th>
                                        <th class="u-text-end">
                                            Genres Total
                                            <i
                                                class="fas fa-info-circle"
                                                title="Total distinct genre tags across this actor's movies in each time window."
                                            ></i>
                                        </th>
                                        <th class="u-text-end">
                                            Movies Total
                                            <i
                                                class="fas fa-info-circle"
                                                title="Total movies for this actor in each time window."
                                            ></i>
                                        </th>
                                        <th class="u-text-end">
                                            Genres Avg
                                            <i
                                                class="fas fa-info-circle"
                                                title="Average genres per period bucket in the selected window."
                                            ></i>
                                        </th>
                                        <th class="u-text-end">
                                            Movies Avg
                                            <i
                                                class="fas fa-info-circle"
                                                title="Average movies per period bucket in the selected window."
                                            ></i>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="window in ['week', 'month', 'year']" :key="`insight-summary-${window}`">
                                        <td class="u-text-capitalize">{{ window }}</td>
                                        <td class="u-text-end">{{ Number(actorInsights.genre_period_counts?.[window]?.total || 0).toLocaleString() }}</td>
                                        <td class="u-text-end">{{ Number(actorInsights.movie_period_counts?.[window]?.total || 0).toLocaleString() }}</td>
                                        <td class="u-text-end">{{ Number(actorInsights.genre_period_counts?.[window]?.avg || 0).toFixed(2) }}</td>
                                        <td class="u-text-end">{{ Number(actorInsights.movie_period_counts?.[window]?.avg || 0).toFixed(2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showActorGenres" class="ui-col-12">
                <div class="ui-card">
                    <div class="ui-card-body">
                        <h6 class="mb-2">
                            All Genres This Actor Has
                            <span class="ui-badge u-bg-secondary ms-1">{{ Number(actorInsights.actor_genres_total || 0).toLocaleString() }}</span>
                            <i
                                class="fas fa-info-circle u-text-muted"
                                title="Distinct genres found from this actor's real movie tags."
                            ></i>
                        </h6>
                        <div class="ui-table-responsive">
                            <table class="ui-table ui-table-sm ui-table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Genre</th>
                                        <th class="u-text-end">Movies Tagged</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="(actorInsights.actor_genres || []).length === 0">
                                        <td colspan="2" class="u-text-muted">No actor genre data.</td>
                                    </tr>
                                    <tr v-for="row in actorInsights.actor_genres || []" :key="`actor-genre-${row.genre}`">
                                        <td>
                                            <Link :href="route('jav.vue.dashboard', { tag: row.genre })" class="u-no-underline">
                                                {{ row.genre }}
                                            </Link>
                                        </td>
                                        <td class="u-text-end">{{ Number(row.count || 0).toLocaleString() }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="ui-alert ui-alert-warning mb-0">
            {{ emptyMessage }}
        </div>
    </div>
</template>
