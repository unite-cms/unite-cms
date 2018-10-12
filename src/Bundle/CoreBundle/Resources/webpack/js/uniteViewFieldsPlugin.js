import Fallback from '../vue/views/Fields/Fallback';

export default {
    install: (Vue, options) => {

        // Use the same plugin instance across multiple files and multiple Vue.use() statements.
        window.UniteCMSViewFieldsPlugin = window.UniteCMSViewFieldsPlugin || {
            _types: {},
            register(type, component) {
                this._types[type] = component;
            },
            resolve(type) {
                type = typeof this._types[type] !== 'undefined' ? type : 'fallback';
                return typeof this._types[type] !== 'undefined' ? this._types[type] : Fallback;
            },
            resolveFieldQueryFunction(type) {
                let findFieldQuery = function (component) {
                    if (typeof component.methods === 'object' && typeof component.methods.fieldQuery === 'function') {
                        return component.methods.fieldQuery;
                    }
                    if (typeof component.extends === 'object') {
                        return findFieldQuery(component.extends);
                    }
                    throw new TypeError('All fields, registered in $uniteCMSViewFields must implement method fieldQuery() or extend a compoenent that implements method fieldQuery().')
                };
                return findFieldQuery(this.resolve(type));
            }
        };

        Vue.$uniteCMSViewFields = window.UniteCMSViewFieldsPlugin;
        Vue.prototype.$uniteCMSViewFields = Vue.$uniteCMSViewFields;

        // Allow to register types on plugin initialization.
        if(options && options.register) {
            Object.keys(options.register).forEach((key) => {
                Vue.$uniteCMSViewFields.register(key, options.register[key]);
            });
        }
    }
};