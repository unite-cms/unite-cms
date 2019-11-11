import Vue from 'vue'
import VueRouter from 'vue-router'
import Dashboard from "../pages/Dashboard";
import Login from "../pages/Login";

import UserState from '../state/User';

Vue.use(VueRouter);

const routes = [
    { path: '/', component: Dashboard, meta: { requiresAuth: true } },
    { path: '/login', component: Login, meta: { requiresAnonymous: true } },
];

export const router = new VueRouter({
    base: UNITE_ADMIN_CONFIG.baseurl || '',
    mode: 'history',
    routes,
});

router.beforeEach((to, from, next) => {

    // If this route requires auth but user is not authenticated
    if(to.matched.some(record => record.meta.requiresAuth) && !UserState.isAuthenticated) {
        next({
            path: '/login',
            query: {redirect: to.fullPath}
        });

    // If this route requires anonymous but user is authenticated
    } else if(to.matched.some(record => record.meta.requiresAnonymous) && UserState.isAuthenticated) {
            next({
                path: '/'
            });

    // User is logged in or public route
    } else {
        next();
    }
});

export default router;
