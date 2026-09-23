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

    async function login(credentials, redirect = null) {
        await axios.get('/sanctum/csrf-cookie', { baseURL: '/' });
        await axios.post('/login', credentials);
        await fetchUser();
        // Honor a post-login redirect (e.g. an invitation accept link that
        // bounced through login), falling back to the Tasks page.
        router.push(redirect || { name: 'tasks' });
    }

    async function logout() {
        await axios.post('/logout');
        user.value = null;
        router.push({ name: 'login' });
    }

    return { user, fetchUser, login, logout };
}
