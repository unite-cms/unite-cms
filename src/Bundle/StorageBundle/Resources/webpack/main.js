
import Vue from "vue";
import vueCustomElement from 'vue-custom-element';

import File from "./vue/field/file.vue";

// Use VueCustomElement
Vue.use(vueCustomElement);

if(!customElements.get('unite-cms-storage-file-field')) {
    Vue.customElement('unite-cms-storage-file-field', File);
}
