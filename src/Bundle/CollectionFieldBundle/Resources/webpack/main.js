
import Vue from "vue";
import vueCustomElement from 'vue-custom-element';

import Collection from "./vue/field/Collection.vue";
import CollectionRow from "./vue/field/CollectionRow.vue";

// Use VueCustomElement
Vue.use(vueCustomElement);

Vue.customElement('united-cms-collection-field-row', CollectionRow);
Vue.customElement('united-cms-collection-field', Collection);