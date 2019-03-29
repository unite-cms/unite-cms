
import Vue from "vue";
import vueCustomElement from 'vue-custom-element';

import moment from 'moment';
import feather from 'feather-icons';
import pageUnload from "./js/pageUnload";
import formGroupErrorHandler from "./js/formGroupErrorHandler";
import uniteViewFieldsPlugin from "./js/uniteViewFieldsPlugin";

import TableView from './vue/views/TableView';
import GridView from './vue/views/GridView';
import TreeView from './vue/views/Tree/TreeView';
import DomainEditor from "./vue/components/DomainEditor.vue";
import ApiTokenField from "./vue/components/ApiTokenField";
import iFramePreview from "./vue/components/iFramePreview.vue";
import VariantsSelect from "./vue/components/VariantsSelect.vue";
import VariantsVariant from "./vue/components/VariantsVariant.vue";
import Reference from "./vue/field/Reference.vue";
import Link from "./vue/field/Link.vue";
import Location from "./vue/field/Location.vue";
import State from "./vue/field/State.vue";
import AutoText from "./vue/field/AutoText.vue";
import ColourPicker from "./vue/field/ColourPicker.vue";

import AceDiff from 'ace-diff/dist/ace-diff.min';
import 'ace-diff/dist/ace-diff.min.css';

require("./sass/unite.scss");

// Use VueCustomElement
Vue.use(vueCustomElement);

// Create unite cms event bus.
window.UniteCMSEventBus = new Vue();

// Register core fields.
Vue.use(uniteViewFieldsPlugin, {
    register: {
        'id': require('./vue/views/Fields/Id').default,
        'text': require('./vue/views/Fields/Text').default,
        'textarea': require('./vue/views/Fields/Textarea').default,
        'auto_text': require('./vue/views/Fields/AutoText').default,
        'date': require('./vue/views/Fields/Date').default,
        'state': require('./vue/views/Fields/State').default,
        'checkbox': require('./vue/views/Fields/Checkbox').default,
        'choice': require('./vue/views/Fields/Choice').default,
        'reference': require('./vue/views/Fields/Reference').default,
        'reference_of': require('./vue/views/Fields/ReferenceOf').default,
    }
});

// Register global unite cms core components.
Vue.customElement('unite-cms-core-domaineditor', DomainEditor);
Vue.customElement('unite-cms-core-variants-select', VariantsSelect);
Vue.customElement('unite-cms-core-variants-variant', VariantsVariant);
Vue.customElement('unite-cms-core-api-token-field', ApiTokenField);
Vue.customElement('unite-cms-core-iframe-preview', iFramePreview);
Vue.customElement('unite-cms-core-reference-field', Reference);
Vue.customElement('unite-cms-core-link-field', Link);
Vue.customElement('unite-cms-core-location-field', Location);
Vue.customElement('unite-cms-core-state-field', State);
Vue.customElement('unite-cms-core-auto-text-field', AutoText);
Vue.customElement('unite-cms-colour-picker-field', ColourPicker);

// Register views.
Vue.customElement('unite-cms-core-view-table', TableView);
Vue.customElement('unite-cms-core-view-grid', GridView);
Vue.customElement('unite-cms-core-view-tree', TreeView);

// Create vue moment filter.
moment.locale(window.navigator.language);

Vue.filter('dateFromNow', function(value) {
    let date = (typeof value === 'string') ? moment(value) : moment.unix(value);
    return value ? date.fromNow() : '';
});

Vue.filter('date', function(value) {
    let date = (typeof value === 'string') ? moment(value) : moment.unix(value);
    return value ? date.format('LL') : '';
});

Vue.filter('dateFull', function(value) {
    let date = (typeof value === 'string') ? moment(value) : moment.unix(value);
    return value ? date.format('LLL') : '';
});


window.onload = function() {

    // Replace all feather icons in html code.
    feather.replace();

    // Add a generic unload warning message to all pages with forms.
    pageUnload.init('You have unsaved changes! Do you really want to navigate away and discard them?');

    // Show error indicator in form group labels for all children.
    formGroupErrorHandler.init();

    let diffVisualization = document.querySelector('.unite-domain-change-visualization');
    if(diffVisualization) {

        let formatJSON = function(value) {
            value = JSON.stringify(JSON.parse(value), null, 2);
            value = value.replace(/^( *)(.*\[)(\],*)$/gm, "$1$2\n$1$3");
            value = value.replace(/^( *)(.*\{)(\},*)$/gm, "$1$2\n$1$3");
            return value;
        };

        let JSONDiff = new AceDiff({
            element: diffVisualization,
            mode: 'ace/mode/json',
            left: {
                content: formatJSON(diffVisualization.dataset.leftContent),
                editable: false,
                copyLinkEnabled: false
            },
            right: {
                content: formatJSON(diffVisualization.dataset.rightContent),
                editable: false,
                copyLinkEnabled: false
            },
        });

        JSONDiff.editors.left.ace.setFontSize(10);
        JSONDiff.editors.right.ace.setFontSize(10);
    }
};