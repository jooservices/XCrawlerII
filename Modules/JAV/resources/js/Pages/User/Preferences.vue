<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { useUIStore } from '@jav/Stores/ui';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';

const props = defineProps({
    preferences: Object,
});

const page = usePage();
const uiStore = useUIStore();
const user = computed(() => page.props.auth?.user ?? null);

const form = useForm({
    show_cover: props.preferences.show_cover ?? false,
    compact_mode: props.preferences.compact_mode || false,
    text_preference: props.preferences.text_preference || 'detailed',
});

const avatarForm = useForm({
    avatar: null,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const profileForm = useForm({
    name: user.value?.name ?? '',
    email: user.value?.email ?? '',
    current_password: '',
});

const isEmailChanged = computed(() => {
    const currentEmail = String(user.value?.email ?? '').trim().toLowerCase();
    const nextEmail = String(profileForm.email ?? '').trim().toLowerCase();

    return currentEmail !== '' && nextEmail !== '' && currentEmail !== nextEmail;
});

const submit = () => {
    form.post(route('jav.preferences.save'), {
        preserveScroll: true,
        onSuccess: () => {
            uiStore.showToast('Preferences updated.', 'success');
        },
        onError: () => {
            uiStore.showToast('Failed to save preferences.', 'error');
        },
    });
};

const onAvatarChange = (event) => {
    avatarForm.avatar = event.target.files?.[0] ?? null;
};

const submitAvatar = () => {
    avatarForm.post(route('jav.account.avatar.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            avatarForm.reset('avatar');
            uiStore.showToast('Avatar updated.', 'success');
        },
        onError: () => {
            uiStore.showToast('Failed to update avatar.', 'error');
        },
    });
};

const removeAvatar = () => {
    avatarForm.delete(route('jav.account.avatar.remove'), {
        preserveScroll: true,
        onSuccess: () => {
            avatarForm.reset('avatar');
            uiStore.showToast('Custom avatar removed. Gravatar restored.', 'success');
        },
        onError: () => {
            uiStore.showToast('Failed to remove avatar.', 'error');
        },
    });
};

const submitPassword = () => {
    passwordForm.put(route('jav.account.password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            uiStore.showToast('Password updated.', 'success');
        },
        onError: () => {
            uiStore.showToast('Failed to update password.', 'error');
        },
    });
};

const submitProfile = () => {
    profileForm.put(route('jav.account.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            profileForm.reset('current_password');
            uiStore.showToast('Profile updated.', 'success');
        },
        onError: () => {
            uiStore.showToast('Failed to update profile.', 'error');
        },
    });
};
</script>

<template>
    <Head>
        <title>Preferences</title>
    </Head>

    <PageShell>
        <template #header>
            <SectionHeader title="User Preferences" subtitle="Manage your avatar, password, and dashboard display behavior" />
        </template>

        <div class="ui-card mb-4">
            <div class="ui-card-body">
                <h5 class="mb-3"><i class="fas fa-user-circle mr-2"></i>Avatar</h5>
                <div class="ui-row ui-items-center ui-g-3">
                    <div class="ui-col-md-2">
                        <img
                            v-if="user?.avatar_url"
                            :src="user.avatar_url"
                            alt="User avatar"
                            width="80"
                            height="80"
                            class="rounded-circle"
                        >
                    </div>
                    <div class="ui-col-md-10">
                        <form @submit.prevent="submitAvatar">
                            <div class="mb-2">
                                <input
                                    type="file"
                                    class="ui-form-control"
                                    accept="image/png,image/jpeg,image/webp"
                                    @change="onAvatarChange"
                                >
                            </div>
                            <div v-if="avatarForm.errors.avatar" class="ui-text-danger small mb-2">{{ avatarForm.errors.avatar }}</div>
                            <button type="submit" class="ui-btn ui-btn-primary" :disabled="avatarForm.processing || !avatarForm.avatar">
                                <i class="fas fa-upload mr-1"></i>Upload Avatar
                            </button>
                            <button
                                v-if="user?.avatar_path"
                                type="button"
                                class="ui-btn ui-btn-outline-secondary ml-2"
                                :disabled="avatarForm.processing"
                                @click="removeAvatar"
                            >
                                <i class="fas fa-undo mr-1"></i>Use Gravatar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="ui-card mb-4">
            <div class="ui-card-body">
                <h5 class="mb-3"><i class="fas fa-id-badge mr-2"></i>Profile</h5>
                <form @submit.prevent="submitProfile">
                    <div class="ui-row ui-g-3">
                        <div class="ui-col-md-6">
                            <label for="profile_name" class="ui-form-label">Name</label>
                            <input id="profile_name" v-model="profileForm.name" type="text" class="ui-form-control" required>
                            <div v-if="profileForm.errors.name" class="ui-text-danger small mt-1">{{ profileForm.errors.name }}</div>
                        </div>
                        <div class="ui-col-md-6">
                            <label for="profile_email" class="ui-form-label">Email</label>
                            <input id="profile_email" v-model="profileForm.email" type="email" class="ui-form-control" required>
                            <div v-if="profileForm.errors.email" class="ui-text-danger small mt-1">{{ profileForm.errors.email }}</div>
                            <div v-if="isEmailChanged" class="u-text-muted small mt-1">
                                You changed email. Please enter current password to confirm.
                            </div>
                        </div>
                        <div class="ui-col-md-6">
                            <label for="profile_current_password" class="ui-form-label">Current Password (required when changing email)</label>
                            <input id="profile_current_password" v-model="profileForm.current_password" type="password" class="ui-form-control" :required="isEmailChanged">
                            <div v-if="profileForm.errors.current_password" class="ui-text-danger small mt-1">{{ profileForm.errors.current_password }}</div>
                        </div>
                    </div>

                    <button type="submit" class="ui-btn ui-btn-primary mt-3" :disabled="profileForm.processing">
                        <i class="fas fa-user-edit mr-1"></i>Update Profile
                    </button>
                </form>
            </div>
        </div>
        <div class="ui-card mb-4">
                <div class="ui-card-body">
                    <h5 class="mb-3"><i class="fas fa-lock mr-2"></i>Change Password</h5>
                    <form @submit.prevent="submitPassword">
                        <div class="ui-row ui-g-3">
                            <div class="ui-col-md-4">
                                <label for="current_password" class="ui-form-label">Current Password</label>
                                <input id="current_password" v-model="passwordForm.current_password" type="password" class="ui-form-control" required>
                                <div v-if="passwordForm.errors.current_password" class="ui-text-danger small mt-1">{{ passwordForm.errors.current_password }}</div>
                            </div>
                            <div class="ui-col-md-4">
                                <label for="password" class="ui-form-label">New Password</label>
                                <input id="password" v-model="passwordForm.password" type="password" class="ui-form-control" required>
                                <div v-if="passwordForm.errors.password" class="ui-text-danger small mt-1">{{ passwordForm.errors.password }}</div>
                            </div>
                            <div class="ui-col-md-4">
                                <label for="password_confirmation" class="ui-form-label">Confirm New Password</label>
                                <input id="password_confirmation" v-model="passwordForm.password_confirmation" type="password" class="ui-form-control" required>
                            </div>
                        </div>

                        <button type="submit" class="ui-btn ui-btn-primary mt-3" :disabled="passwordForm.processing">
                            <i class="fas fa-key mr-1"></i>Update Password
                        </button>
                    </form>
                </div>
            </div>

        <div class="ui-card">
                <div class="ui-card-body">
                    <form @submit.prevent="submit">
                        <div class="ui-row ui-g-3 mb-3">
                            <div class="ui-col-md-6">
                                <label for="text_preference" class="ui-form-label">Text Preference</label>
                                <select id="text_preference" v-model="form.text_preference" name="text_preference" class="ui-form-select">
                                    <option value="detailed">Detailed</option>
                                    <option value="concise">Concise</option>
                                </select>
                            </div>
                        </div>

                        <hr>

                        <div class="ui-form-check mb-2">
                            <input id="show_cover" v-model="form.show_cover" class="ui-form-check-input" type="checkbox" name="show_cover" :value="1">
                            <label class="ui-form-check-label" for="show_cover">Show cover images (overrides `SHOW_COVER`)</label>
                        </div>

                        <div class="ui-form-check mb-4">
                            <input id="compact_mode" v-model="form.compact_mode" class="ui-form-check-input" type="checkbox" name="compact_mode" :value="1">
                            <label class="ui-form-check-label" for="compact_mode">Compact mode (smaller cards)</label>
                        </div>

                        <button type="submit" class="ui-btn ui-btn-primary" :disabled="form.processing">
                            <i class="fas fa-save mr-1"></i>Save Preferences
                        </button>
                    </form>
                </div>
            </div>
            </PageShell>
</template>
