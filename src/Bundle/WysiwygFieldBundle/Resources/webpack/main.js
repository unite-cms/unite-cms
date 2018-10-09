
import Vue from "vue";
import vueCustomElement from 'vue-custom-element';
import Wysiwyg from "./vue/field/Wysiwyg.vue";
import uniteViewFieldsPlugin from "../../../CoreBundle/Resources/webpack/js/uniteViewFieldsPlugin";

// Use VueCustomElement
Vue.use(vueCustomElement);

if(!customElements.get('unite-cms-wysiwyg-field')) {
    Vue.customElement('unite-cms-wysiwyg-field', Wysiwyg);
}

// Register wysiwyg field.
Vue.use(uniteViewFieldsPlugin, {
    register: {
        'wysiwyg': require('../../../CoreBundle/Resources/webpack/vue/views/Fields/Textarea').default,
    }
});
