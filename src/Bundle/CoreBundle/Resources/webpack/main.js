
import Vue from "vue";
import 'document-register-element';
import vueCustomElement from 'vue-custom-element';

import feather from 'feather-icons';

import Table from "./vue/views/Table.vue";
import Sortable from "./vue/views/Sortable.vue";
import DomainEditor from "./vue/components/DomainEditor.vue";
import ApiTokenField from "./vue/components/ApiTokenField";
import iFramePreview from "./vue/components/iFramePreview.vue";
import Reference from "./vue/field/Reference.vue";

require("./sass/unite.scss");

// Use VueCustomElement
Vue.use(vueCustomElement);

window.UniteCMSEventBus = new Vue();

// Register View: Table
Vue.customElement('unite-cms-core-view-table', Table);
Vue.customElement('unite-cms-core-view-sortable', Sortable);
Vue.customElement('unite-cms-core-domaineditor', DomainEditor);
Vue.customElement('unite-cms-core-api-token-field', ApiTokenField);
Vue.customElement('unite-cms-core-iframe-preview', iFramePreview);
Vue.customElement('unite-cms-core-reference-field', Reference);


window.onload = function(e) {
    // Use feather icon set.
    feather.replace();
};
