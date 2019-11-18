import Vue from 'vue';
import apolloProvider from './vue/plugins/apollo';
import i18n from './vue/plugins/i18n';
import router from './vue/plugins/router';
import UniteAdminApp from './vue/App';

require('./app.scss');

new Vue({
    el: '#app',
    apolloProvider,
    router,
    i18n,
    components: { UniteAdminApp },
});
