import { computed, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';

export function useLoginForm() {
    const form = useForm({
        login: '',
        password: '',
        remember: false,
    });

    const clientErrors = reactive({
        login: undefined,
        password: undefined,
    });

    const loginHint = computed(() => {
        if (!form.login.includes('@')) {
            return '';
        }

        const basicEmailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return basicEmailPattern.test(form.login) ? '' : 'Email format looks incomplete.';
    });

    const errors = computed(() => ({
        login: clientErrors.login ?? form.errors.login,
        password: clientErrors.password ?? form.errors.password,
    }));

    const validate = () => {
        clientErrors.login = undefined;
        clientErrors.password = undefined;

        form.login = form.login.trim();

        if (form.login.length === 0) {
            clientErrors.login = 'Username or email is required.';
        }

        if (form.password.length === 0) {
            clientErrors.password = 'Password is required.';
        }

        return !clientErrors.login && !clientErrors.password;
    };

    const submit = () => {
        if (!validate()) {
            return;
        }

        form.post(window.route('v1.action.auth.login', undefined, false), {
            onFinish: () => {
                form.reset('password');
            },
        });
    };

    return {
        form,
        errors,
        loginHint,
        submit,
    };
}
