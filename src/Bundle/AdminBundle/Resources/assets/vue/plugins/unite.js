
import Vue from 'vue';
import gql from 'graphql-tag';

import _fallback from "../components/Fields/List/_fallback";
import User from "../state/User";

export const UniteFallbackFieldType = {
    listComponent: _fallback,
};

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

const FilterFragment = gql`fragment filter on UniteFilter {
    field
    path
    operator
    value
    AND {
        field
        path
        operator
        value
        AND {
            field
            path
            operator
            value
            AND {
                field
                path
                operator
                value
            }
            OR {
                field
                path
                operator
                value
            }
        }
        OR {
            field
            path
            operator
            value
            AND {
                field
                path
                operator
                value
            }
            OR {
                field
                path
                operator
                value
            }
        }
    }
    OR {
        field
        path
        operator
        value
        AND {
            field
            path
            operator
            value
            AND {
                field
                path
                operator
                value
            }
            OR {
                field
                path
                operator
                value
            }
        }
        OR {
            field
            path
            operator
            value
            AND {
                field
                path
                operator
                value
            }
            OR {
                field
                path
                operator
                value
            }
        }
    }
}`;

export const Unite = new Vue({
    data() {
        return {
            loaded: false,
            fieldTypes: {},
            adminViews: {},
        }
    },
    created() {
        this.$on('registerFieldType', (type, field) => {
            this.fieldTypes[type] = field;
        });

        this.$on('load', (data, success, fail, fin) => {

            if(!User.isAuthenticated) {
                if(success) { success(); }
                return;
            }

            this.loaded = false;
            this.$apollo.query({
                query: gql`
                    ${ FilterFragment }
                    query {
                        unite {
                            adminViews {
                                id
                                name
                                type
                                category
                                fragment
                                limit
                                orderBy {
                                    field
                                    order
                                }
                                filter {
                                    ... filter
                                }
                                fields {
                                    id
                                    type
                                    name
                                }
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

            }).catch(fail).catch(fin).then(success);
        });
    },
    methods: {
        getFieldType(type) {
            return this.fieldTypes[type] || UniteFallbackFieldType;
        },
    }
});

export const VueUnite = {
    install: function(Vue, options){
        Vue.prototype.$unite = Unite;
    }
};
