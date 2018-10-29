
import Vue from "vue";
import uniteViewFieldsPlugin from "../../../CoreBundle/Resources/webpack/js/uniteViewFieldsPlugin";

// Register variants fields.
Vue.use(uniteViewFieldsPlugin, {
    register: {
        'variants': require('./vue/views/Fields/Variants').default,
    }
});
