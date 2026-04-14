import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '@/pages/HomePage.vue';
import CategoryPage from '@/pages/CategoryPage.vue';

export const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: HomePage },
        { path: '/categories', redirect: '/' },
        {
            path: '/categories/:pathMatch(.+)',
            name: 'category',
            component: CategoryPage,
            props: true,
        },
    ],
});
