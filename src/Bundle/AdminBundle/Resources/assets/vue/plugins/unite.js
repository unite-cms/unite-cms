
import Vue from 'vue';
import gql from 'graphql-tag';
import {getIntrospectionQuery} from 'graphql'
import { IntrospectionFragmentMatcher } from 'apollo-cache-inmemory';
import ListFieldTypeFallback from "../components/Fields/List/_fallback";
import FormFieldTypeFallback from "../components/Fields/Form/_fallback";
import ViewTypeFallback from "../components/Views/_fallback";
import User from "../state/User";
import router from "./router";
import Mustache from 'mustache';
import Alerts from "../state/Alerts";

const MAX_QUERY_DEPTH = 12;

export const removeIntroSpecType = function(val){
    if(val && typeof val === 'object') {

        if(Array.isArray(val)) {
            val = val.map(removeIntroSpecType);
        } else {
            Object.keys(val).forEach((key) => {
                if(key === '__typename') {
                    delete val[key];
                } else {
                    val[key] = removeIntroSpecType(val[key]);
                }
            });
        }
    }

    return val;
};

export const innerType = function(type) {
    return type.ofType ? innerType(type.ofType) : type.name;
};

export const getAdminViewByType = function(unite, returnType) {

    // For unions, we need to return a special object.
    let rawType = unite.getRawType(returnType);
    if(rawType.kind === 'UNION') {
        return {
            category: 'union',
            rawType: rawType,
            possibleViews: rawType.possibleTypes.map((possibleType) => {
                return getAdminViewByType(unite, possibleType.name);
            }),
            queryFormData(depth) {
                return this.possibleViews.map((possibleView) => {
                    return `... on ${ possibleView.type } { ${ possibleView.queryFormData(depth) } }`
                });
            }
        };
    }

    let embeddedView = Object.values(unite.adminViews).filter((view) => {
        return view.type === returnType;
    });
    return embeddedView.length > 0 ? embeddedView[0] : null;
};

const createAdminView = function (view, unite) {
    view = removeIntroSpecType(view);
    view.listFields = function(){ return this.fields.filter(field => field.show_in_list); };
    view.formFields = function(){ return this.fields.filter(field => field.show_in_form); };

    view.rawType = unite.getRawType(view.type);

    view.contentTitle = function(content){
        return Mustache.render(view.titlePattern, Object.assign({}, content, {
            _name: view.name,
            _category: view.category,
        }));
    };

    if(!view.rawType) {
        return null;
    }

    view.fields.forEach((field) => {

        // Set raw field to field
        view.rawType.fields.forEach((rawField) => {
            if(field.type === rawField.name) {
                field.rawField = rawField;
            }
        });

        // parse field config.
        if(Array.isArray(field.config)) {
            let rawConfig = field.config;
            field.config = {};
            rawConfig.forEach((row) => {
                field.config[row.key] = JSON.parse(row.value);
            });
        }

        // normalize returnType
        field.returnType = innerType(field.rawField.type);

    });

    /**
     * Returns an array with field query statements for all form fields of this view.
     * @returns Array
     */
    view.queryFormData = function(depth = 0){

        if(depth >= MAX_QUERY_DEPTH) {
            return ['__typename'];
        }

        return this.formFields().filter((field) => {
            return !!unite.getFormFieldType(field.fieldType).queryData;
        }).map((field) => {
            return unite.getFormFieldType(field.fieldType).queryData(field, unite, depth);
        });
    };

    /**
     * Returns an object with all normalized values for field internal use.
     * @returns Object
     */
    view.normalizeQueryData = function(queryData = {}, depth = 0){

        if(depth >= MAX_QUERY_DEPTH) {
            return queryData;
        }

        let data = {};
        this.formFields().forEach((field) => {
            let type = unite.getFormFieldType(field.fieldType);
            let fieldData = queryData[field.id] || undefined;
            data[field.id] = !!type.normalizeQueryData ? type.normalizeQueryData(fieldData, field, unite, depth) : fieldData;
        });
        return data;
    };

    /**
     * Returns an object with all normalized values for used in a create / update mutation
     * @returns Object
     */
    view.normalizeMutationData = function(formData = {}, depth = 0){

        if(depth >= MAX_QUERY_DEPTH) {
            return formData;
        }

        let data = {};
        this.formFields().forEach((field) => {
            let type = unite.getFormFieldType(field.fieldType);
            let fieldData = formData[field.id] || undefined;
            data[field.id] = !!type.normalizeMutationData ? type.normalizeMutationData(fieldData, field, unite, depth) : fieldData;
        });
        return data;
    };

    return view;
};

export const Unite = new Vue({

    data() {
        return {
            loaded: false,
            rawTypes: [],
            listFieldTypes: {},
            formFieldTypes: {},
            viewTypes: {},
            adminViews: {},
            permissions: {
                LOGS: false,
                SCHEMA: false,
                QUERY_EXPLORER: false,
            }
        }
    },
    created() {
        this.$on('registerListFieldType', (type, field) => {
            this.listFieldTypes[type] = field;
        });

        this.$on('registerFormFieldType', (type, field) => {
            this.formFieldTypes[type] = field;
        });

        this.$on('registerViewType', (type, view) => {
            this.viewTypes[type] = view;
        });

        this.$on('load', (reload = false, success, fail, fin) => {
            this.loadAdminViews(reload, success, fail, fin);
        });

        User.$watch('user.token', () => {
            this.loadAdminViews();
        });
    },
    computed: {
        adminViewsFragment() {

            let fragments = [];
            let fragmentNames = [];
            Object.keys(this.viewTypes).forEach((type) => {
                if(this.viewTypes[type].fragments && this.viewTypes[type].fragments.adminView) {
                    fragmentNames.push(`... ${type}Fragment`);
                    fragments.push(this.viewTypes[type].fragments.adminView.loc.source.body);
                }
            });

            return `
                ${ fragments.join("\n") }
                
                fragment adminViews on UniteAdminView {
                    viewType :__typename
                    id
                    type
                    name
                    titlePattern
                    fragment
                    category
                    fields {
                        id
                        name
                        description
                        type
                        fieldType
                        non_null
                        required
                        list_of
                        show_in_list
                        show_in_form
                        form_group
                        config {
                            key
                            value
                        }
                    }
                    permissions {
                        create
                    }
                    ${fragmentNames.join("\n")}
                }`;
        },

        /**
         * Get the current view for the current route or null.
         *
         * @returns {*|{extends}}
         */
        currentView() {
            return this.adminViews[router.currentRoute.params.type];
        },
    },
    methods: {

        /**
         * Do a full schema introspection and load all adminViews.
         *
         * @param reload
         * @param success
         * @param fail
         * @param fin
         */
        loadAdminViews(reload, success, fail, fin) {

            if(!User.isAuthenticated || (!reload && this.loaded)) {
                if(success) { success(); }
                return;
            }

            this.loaded = false;

            this.$apollo.query({
                query: gql(getIntrospectionQuery()),
            }).then((data) => {
                this.rawTypes = data.data.__schema.types;
                this.fragmentMatcher.possibleTypesMap = this.fragmentMatcher.parseIntrospectionResult(data.data);

                let uniteType = this.rawTypes.filter(type => type.name === 'UniteQuery');
                if(uniteType.length === 0) {
                    Alerts.$emit('push', 'danger', 'You are not allowed to access the admin views.');
                    throw 'You are not allowed to access the admin views.';
                }

                uniteType = uniteType[0];
                let adminViewFields = uniteType.fields.filter(field => field.name === 'adminViews');
                if(adminViewFields.length === 0) {
                    Alerts.$emit('push', 'danger', 'You are not allowed to access the admin views.');
                    throw 'You are not allowed to access the admin views.';
                }
                this.$apollo.query({
                    query: gql`
                        ${ this.adminViewsFragment }
                        query {
                            unite {
                                adminViews {
                                    ... adminViews
                                }
                                adminPermissions {
                                    LOGS
                                    SCHEMA
                                    QUERY_EXPLORER
                                }
                            }
                        }
                    `,
                }).then((data) => {

                    this.permissions = data.data.unite.adminPermissions;
                    this.adminViews = [];
                    data.data.unite.adminViews.forEach((view) => {
                        view = createAdminView(view, this);
                        if(view) {
                            this.adminViews[view.id] = view;
                        }
                    });
                    this.loaded = true;
                    this.$emit('loaded');

                }).catch(fail).finally(fin).then(success);


            }).catch((error) => {
                User.$emit('logout', {}, () => {
                    router.push('/login');
                })
            });
        },

        /**
         * Returns a rawType for the given name.
         *
         * @param typeName
         * @returns {*|{extends}}
         */
        getRawType(typeName) {
            let found = this.rawTypes.filter((type) => { return type.name === typeName });
            return found.length > 0 ? found[0] : null;
        },

        /**
         * Get all registered list field types.
         *
         * @param type
         * @returns {*|{extends}}
         */
        getListFieldType(type) {
            return this.listFieldTypes[type] || ListFieldTypeFallback;
        },

        /**
         * Get all registered form field types.
         *
         * @param type
         * @returns {*|{extends}}
         */
        getFormFieldType(type) {
            return this.formFieldTypes[type] || FormFieldTypeFallback;
        },

        /**
         * Get all registered view field types.
         *
         * @param type
         * @returns {*|{extends}}
         */
        getViewType(type) {
            return this.viewTypes[type] || ViewTypeFallback;
        },
    }
});

Unite.fragmentMatcher = new IntrospectionFragmentMatcher({
    introspectionQueryResultData: {
        __schema: {
            types: [],
        },
    },
});

export const VueUnite = {
    install: function(Vue, options){
        Vue.prototype.$unite = Unite;
    }
};
