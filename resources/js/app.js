import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import * as Sentry from '@sentry/vue';
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
        name: 'home',
        component: () => import('./pages/Landing.vue'),
        meta: { guest: true },
    },
    {
        path: '/timer',
        name: 'timer',
        component: () => import('./pages/Timer.vue'),
        meta: { auth: true },
    },
    {
        path: '/tasks',
        name: 'tasks',
        component: () => import('./pages/Tasks.vue'),
        meta: { auth: true },
    },
    {
        path: '/reports',
        name: 'reports',
        component: () => import('./pages/Reports.vue'),
        meta: { auth: true },
    },
    {
        path: '/settings',
        name: 'settings',
        component: () => import('./pages/Settings.vue'),
        meta: { auth: true },
    },
    {
        path: '/automations',
        name: 'automations',
        component: () => import('./pages/Automations.vue'),
        meta: { auth: true },
    },
    {
        path: '/profile',
        name: 'profile',
        component: () => import('./pages/Profile.vue'),
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
        return { name: 'timer' };
    }
});

const app = createApp(App);

// Frontend error reporting. The DSN is baked in at build time via Vite; when
// it's absent (e.g. local dev without a DSN) Sentry is simply not initialised.
// This reuses the same Sentry project as the Laravel backend.
if (import.meta.env.VITE_SENTRY_DSN) {
    // Errors only — no browserTracingIntegration, so the heavy performance
    // tracing code is tree-shaken out (~50KB gzip saved). Add it back later if
    // you want performance monitoring.
    Sentry.init({
        app,
        dsn: import.meta.env.VITE_SENTRY_DSN,
        environment: import.meta.env.VITE_SENTRY_ENVIRONMENT
            || (import.meta.env.PROD ? 'production' : 'local'),
    });
}

app.use(router);
app.mount('#app');
