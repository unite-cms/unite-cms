
import Vue from "vue";
import 'document-register-element';
import vueCustomElement from 'vue-custom-element';

import feather from 'feather-icons';
import UnloadManager from "./js/pageUnload";

import BaseView from './vue/views/Base/BaseView.vue';
import TableContent from './vue/views/TableContent.vue';
import Sortable from "./vue/views/Sortable.vue";
import DomainEditor from "./vue/components/DomainEditor.vue";
import ApiTokenField from "./vue/components/ApiTokenField";
import iFramePreview from "./vue/components/iFramePreview.vue";
import VariantsSelect from "./vue/components/VariantsSelect.vue";
import VariantsVariant from "./vue/components/VariantsVariant.vue";
import Reference from "./vue/field/Reference.vue";
import Link from "./vue/field/Link.vue";
import State from "./vue/field/State.vue";

require("./sass/unite.scss");

// Use VueCustomElement
Vue.use(vueCustomElement);

// Create unite cms event bus.
window.UniteCMSEventBus = new Vue();

// Register global unite cms core components.
Vue.customElement('unite-cms-core-view-sortable', Sortable);
Vue.customElement('unite-cms-core-domaineditor', DomainEditor);
Vue.customElement('unite-cms-core-variants-select', VariantsSelect);
Vue.customElement('unite-cms-core-variants-variant', VariantsVariant);
Vue.customElement('unite-cms-core-api-token-field', ApiTokenField);
Vue.customElement('unite-cms-core-iframe-preview', iFramePreview);
Vue.customElement('unite-cms-core-reference-field', Reference);
Vue.customElement('unite-cms-core-link-field', Link);
Vue.customElement('unite-cms-core-state-field', State);

// Register views.
Vue.customElement('unite-cms-core-view-table', { extends: BaseView, contentComponent: TableContent });

// Register view fields.
Vue.use({
    install: (Vue) => {
        Vue.prototype.$uniteCMSViewFields = {
            _types: {},
            register(type, component) {
                this._types[type] = component;
            },
            resolve(type) {
                type = typeof this._types[type] !== 'undefined' ? type : 'fallback';
                return typeof this._types[type] !== 'undefined' ? this._types[type] : require('./vue/views/Fields/Fallback').default;
            },
            resolveFieldQueryFunction(type) {
                let findFieldQuery = function(component) {
                    if(typeof component.methods === 'object' && typeof component.methods.fieldQuery === 'function') {
                        return component.methods.fieldQuery;
                    }
                    if(typeof component.extends === 'object') {
                        return findFieldQuery(component.extends);
                    }
                    throw new TypeError('All fields, registered in $uniteCMSViewFields must implement method fieldQuery() or extend a compoenent that implements method fieldQuery().')
                };
                return findFieldQuery(this.resolve(type));
            }
        };

        Vue.prototype.$uniteCMSViewFields.register('text', require('./vue/views/Fields/Text').default);
        Vue.prototype.$uniteCMSViewFields.register('textarea', require('./vue/views/Fields/Textarea').default);
        Vue.prototype.$uniteCMSViewFields.register('image', require('./vue/views/Fields/Image').default);
    }
});

window.onload = function() {

    // Use feather icon set.
    feather.replace();

    // Add a generic unload warning message to all pages with forms.
    UnloadManager.init('You have unsaved changes! Do you really want to navigate away and discard them?');
};