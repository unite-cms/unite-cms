
import Vue from 'vue';
import _fallback from "../components/Fields/List/_fallback";

export const UniteFallbackFieldType = {
    listComponent: _fallback,
    fieldQuery: (id) => id,
};

export const Unite = new Vue({
    data() {
        return {
            fieldTypes: {}
        }
    },
    created() {
        this.$on('registerFieldType', (type, field) => {
            this.fieldTypes[type] = field;
        });
    },
    methods: {
        getFieldType(contentTypeId, fieldId) {
            // TODO: Implement
            return UniteFallbackFieldType;
        }
    },
});

export const VueUnite = {
    fields: [],

    install: function(Vue, options){
        Vue.prototype.$unite = Unite;
    }
};
