import Fallback from '../vue/views/Fields/Fallback';

let findComponentMethod = (component, name) => {
    if (typeof component.methods === 'object' && typeof component.methods[name] === 'function') {
        return component.methods[name];
    }
    if (typeof component.extends === 'object') {
        return findComponentMethod(component.extends, name);
    }
    throw new TypeError('All fields, registered in $uniteCMSViewFields must implement method ' + name + '() or extend a component that implements this method.')
};

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
                return findComponentMethod(this.resolve(type), 'fieldQuery');
            },

            resolveFilterQueryFunction(type) {
                return findComponentMethod(this.resolve(type), 'filterQuery');
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