import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import axios from 'axios';

// Configure axios defaults
axios.defaults.baseURL = '/api';
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
axios.defaults.headers.common['Accept'] = 'application/json';

// Router setup
const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('./pages/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('./pages/Register.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        name: 'dashboard',
        component: () => import('./pages/Dashboard.vue'),
        meta: { auth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard - only check auth for routes that need it
router.beforeEach(async (to, from) => {
    if (!to.meta.auth && !to.meta.guest) return;

    let user = null;
    try {
        const { data } = await axios.get('/user');
        user = data;
    } catch {
        user = null;
    }

    if (to.meta.auth && !user) {
        return { name: 'login' };
    }

    if (to.meta.guest && user) {
        return { name: 'dashboard' };
    }
});

const app = createApp(App);
app.use(router);
app.mount('#app');
