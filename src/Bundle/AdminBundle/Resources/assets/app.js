
import Vue from 'vue';
import apolloProvider from './vue/plugins/apollo';
import router from './vue/plugins/router';
import UniteAdminApp from './vue/App';

////////// INIT STYLES //////////
require('./app.scss');


////////// INIT VUE //////////
new Vue({
    el: '#app',
    apolloProvider,
    router,
    components: { UniteAdminApp },
});

