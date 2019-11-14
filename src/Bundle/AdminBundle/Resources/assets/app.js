
import Vue from 'vue';
import State from './vue/state';
import apolloProvider from './vue/plugins/apollo';
import i18n from './vue/plugins/i18n';
import { Unite, VueUnite } from './vue/plugins/unite';
import router from './vue/plugins/router';

import UniteAdminApp from './vue/App';
import Text from "./vue/components/Fields/List/Text";


////////// INIT UIKIT //////////
import UIkit from 'uikit';



////////// INIT STYLES //////////
require('./app.scss');



////////// INIT STATE //////////
Object.keys(State).forEach((state) => {
    State[state].$apolloProvider = apolloProvider;
    State[state].$emit('load');
});



////////// INIT UNITE //////////
Vue.use(VueUnite);
Unite.$apolloProvider = apolloProvider;
Unite.$emit('load');
Unite.$emit('registerFieldType', 'text', {
    listComponent: Text,
});


////////// INIT VUE //////////
new Vue({
    el: '#app',
    apolloProvider,
    router,
    i18n,
    components: { UniteAdminApp },
});
