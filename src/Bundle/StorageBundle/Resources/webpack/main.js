
import Vue from "vue";
import vueCustomElement from 'vue-custom-element';

import File from "./vue/field/file.vue";
import uniteViewFieldsPlugin from "../../../CoreBundle/Resources/webpack/js/uniteViewFieldsPlugin";

// Use VueCustomElement
Vue.use(vueCustomElement);

if(!customElements.get('unite-cms-storage-file-field')) {
    Vue.customElement('unite-cms-storage-file-field', File);
}

// Register storage fields.
Vue.use(uniteViewFieldsPlugin, {
    register: {
        'image': require('./vue/views/Fields/Image').default,
    }
});
