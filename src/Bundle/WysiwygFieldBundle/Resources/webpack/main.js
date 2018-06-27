
import Vue from "vue";
import vueCustomElement from 'vue-custom-element';
import Wysiwyg from "./vue/field/Wysiwyg.vue";

// Use VueCustomElement
Vue.use(vueCustomElement);

Vue.customElement('unite-cms-wysiwyg-field', Wysiwyg);
