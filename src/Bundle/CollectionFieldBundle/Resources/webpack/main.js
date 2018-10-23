
import Vue from "vue";
import vueCustomElement from 'vue-custom-element';
import uniteViewFieldsPlugin from "../../../CoreBundle/Resources/webpack/js/uniteViewFieldsPlugin";

import Collection from "./vue/field/Collection.vue";
import CollectionRow from "./vue/field/CollectionRow.vue";

// Use VueCustomElement
Vue.use(vueCustomElement);

Vue.customElement('unite-cms-collection-field-row', CollectionRow);
Vue.customElement('unite-cms-collection-field', Collection);

// Register collection fields.
Vue.use(uniteViewFieldsPlugin, {
    register: {
        'collection': require('./vue/views/Fields/Collection').default,
    }
});
