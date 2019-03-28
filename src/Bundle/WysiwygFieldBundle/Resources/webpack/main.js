
import Vue from "vue";
import vueCustomElement from 'vue-custom-element';
import CKEditor from "@ckeditor/ckeditor5-vue";

import Wysiwyg from "./vue/field/Wysiwyg.vue";

// Use VueCustomElement
Vue.use(vueCustomElement);
Vue.use(CKEditor);

if(!customElements.get('unite-cms-wysiwyg-field')) {
    Vue.customElement('unite-cms-wysiwyg-field', Wysiwyg);
}