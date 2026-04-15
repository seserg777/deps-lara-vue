import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '@/pages/HomePage.vue';
import CartPage from '@/pages/CartPage.vue';
import CategoryPage from '@/pages/CategoryPage.vue';
import SearchPage from '@/pages/SearchPage.vue';

export const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: HomePage },
        { path: '/search', name: 'search', component: SearchPage },
        { path: '/cart', name: 'cart', component: CartPage },
        { path: '/categories', redirect: '/' },
        {
            path: '/categories/:pathMatch(.+)',
            name: 'category',
            component: CategoryPage,
            props: true,
        },
    ],
});
