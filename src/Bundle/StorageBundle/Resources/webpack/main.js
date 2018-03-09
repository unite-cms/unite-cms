
import Vue from "vue";
import vueCustomElement from 'vue-custom-element';

import File from "./vue/field/file.vue";

// Use VueCustomElement
Vue.use(vueCustomElement);

if(!customElements.get('united-cms-storage-file-field')) {
    Vue.customElement('united-cms-storage-file-field', File);
}