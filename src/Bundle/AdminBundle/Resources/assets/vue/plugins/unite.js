
import Vue from 'vue';
import gql from 'graphql-tag';
import {getIntrospectionQuery} from 'graphql'
import { IntrospectionFragmentMatcher } from 'apollo-cache-inmemory';
import ListFieldTypeFallback from "../components/Fields/List/_fallback";
import ViewTypeFallback from "../components/Views/_fallback";
import User from "../state/User";

const removeIntroSpecType = function(val){
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

export const Unite = new Vue({

    data() {
        return {
            loaded: false,
            listFieldTypes: {},
            viewTypes: {},
            adminViews: {},
        }
    },
    created() {
        this.$on('registerListFieldType', (type, field) => {
            this.listFieldTypes[type] = field;
        });

        this.$on('registerViewType', (type, view) => {
            this.viewTypes[type] = view;
        });

        this.$on('load', (data, success, fail, fin) => {
            this.loadAdminViews(data, success, fail, fin);
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
                    fragment
                    category
                    fields {
                        id
                        type
                        name
                    }
                    ${fragmentNames.join("\n")}
                }`;
        },
    },
    methods: {

        /**
         * Do a full schema introspection and load all adminViews.
         *
         * @param data
         * @param success
         * @param fail
         * @param fin
         */
        loadAdminViews(data, success, fail, fin) {

            if(!User.isAuthenticated) {
                if(success) { success(); }
                return;
            }

            this.loaded = false;

            this.$apollo.query({
                query: gql(getIntrospectionQuery()),
            }).then((data) => {
                this.fragmentMatcher.possibleTypesMap = this.fragmentMatcher.parseIntrospectionResult(data.data);

                setTimeout(() => {
                    this.$apollo.query({
                        query: gql`
                            ${ this.adminViewsFragment }
                            query {
                                unite {
                                    adminViews {
                                        ... adminViews
                                    }
                                }
                            }
                        `,
                    }).then((data) => {
                        this.adminViews = [];
                        data.data.unite.adminViews.forEach((view) => {
                            this.adminViews[view.id] = removeIntroSpecType(view);
                        });

                        this.loaded = true;
                        this.$emit('loaded');

                    }).catch(fail).finally(fin).then(success);
                }, 10);
            });
        },

        getListFieldType(type) {
            return this.listFieldTypes[type] || ListFieldTypeFallback;
        },

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
