import Vue from 'vue'
import VueRouter from 'vue-router'
import User from '../state/User';
import Alerts from "../state/Alerts";
import Route from "../state/Route";
import { Unite } from "./unite";

import Dashboard from "../pages/Dashboard";
import Login from "../pages/Login";
import Explorer from "../pages/Explorer";
import Schema from "../pages/Schema";
import Logs from "../pages/Logs";
import Container from "../pages/content/Container";
import Create from "../pages/content/Create";
import Update from "../pages/content/Update";
import Index from "../pages/content/List";
import PermanentDelete from "../pages/content/PermanentDelete";
import Recover from "../pages/content/Recover";
import Delete from "../pages/content/Delete";
import Revert from "../pages/content/Revert";
import UserInvite from "../pages/content/UserInvite";

Vue.use(VueRouter);

const routes = [
    { path: '/', component: Dashboard, meta: { requiresAuth: true } },
    { path: '/login', component: Login, meta: { requiresAnonymous: true } },
    { path: '/explorer', component: Explorer, meta: { requiresAuth: true } },
    { path: '/schema', component: Schema, meta: { requiresAuth: true } },
    { path: '/logs', component: Logs, meta: { requiresAuth: true } },

    { path: '/content/:type', component: Container, children: [
        { path: '', component: Index } ,
        { path: 'create', component: Create },
        { path: ':id/update', component: Update },
        { path: ':id/revert', component: Revert },
        { path: ':id/delete', component: Delete },
        { path: ':id/recover', component: Recover },
        { path: ':id/permanent_delete', component: PermanentDelete },
    ], meta: { requiresAuth: true } },
    { path: '/user/:type', component: Container, children: [
        { path: '',  component: Index },
        { path: 'create', component: Create },
        { path: ':id/update', component: Update },
        { path: ':id/revert', component: Revert },
        { path: ':id/delete', component: Delete },
        { path: ':id/recover', component: Recover },
        { path: ':id/permanent_delete', component: PermanentDelete },
        { path: ':id/user_invite', component: UserInvite },
    ], meta: { requiresAuth: true } },
    { path: '/setting/:type', component: Container, children: [
            { path: '', component: Index },
            { path: 'revert', component: Revert },
    ], meta: { requiresAuth: true } },
];

export const router = new VueRouter({
    base: UNITE_ADMIN_CONFIG.baseurl || '',
    mode: 'history',
    routes,
});

router.beforeEach((to, from, next) => {

    // Clear all alerts on page change.
    Alerts.$emit('clear');

    // If this route requires auth but user is not authenticated
    if(to.matched.some(record => record.meta.requiresAuth) && !User.isAuthenticated) {
        next({
            path: '/login',
            query: {redirect: to.fullPath}
        });

    // If this route requires anonymous but user is authenticated
    } else if(to.matched.some(record => record.meta.requiresAnonymous) && User.isAuthenticated) {
            next({
                path: '/'
            });

    // User is logged in or public route
    } else {

        // Store the previous route.
        Route.$emit('setPreviousRoute', from);

        // Make sure that adminViews are loaded for all logged in routes.
        if(User.isAuthenticated) {
            Unite.$emit('load', false, next);
        } else {
            next();
        }
    }
});

export default router;
