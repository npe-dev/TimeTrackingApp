import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const user = ref(null);

export function useAuth() {
    const router = useRouter();

    async function fetchUser() {
        try {
            const { data } = await axios.get('/user');
            user.value = data;
        } catch {
            user.value = null;
        }
        return user.value;
    }

    async function login(credentials) {
        await axios.get('/sanctum/csrf-cookie', { baseURL: '/' });
        await axios.post('/login', credentials);
        await fetchUser();
        router.push({ name: 'dashboard' });
    }

    async function register(form) {
        await axios.get('/sanctum/csrf-cookie', { baseURL: '/' });
        await axios.post('/register', form);
        await fetchUser();
        router.push({ name: 'dashboard' });
    }

    async function logout() {
        await axios.post('/logout');
        user.value = null;
        router.push({ name: 'login' });
    }

    return { user, fetchUser, login, register, logout };
}
