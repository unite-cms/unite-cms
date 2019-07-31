<template>
    <div class="view-field view-field-variants" v-if="value">
        <div class="uk-label" v-if="field.settings.title">{{ field.settings.variant_titles[value.type] }}</div>
        <component :key="identifier" v-for="(v,identifier) in field.settings.on[value.type]"
                   :is="$uniteCMSViewFields.resolve(v.type)"
                   :config="config"
                   :field="Object.assign({}, v, { identifier: identifier, label: '' })"
                   :row="createRow(value)"></component>
    </div>
</template>

<script>
    import BaseField from '../../../../../../CoreBundle/Resources/webpack/vue/views/Base/AbstractRowField';
    import { createRow } from '../../../../../../CoreBundle/Resources/webpack/vue/views/Base/ViewRow';

    export default {
        extends: BaseField,
        methods: {

            createRow(row) {
                return createRow(row, this.config.contentType);
            },

            /**
             * @inheritdoc
             */
            fieldQuery(identifier, field, $uniteCMSViewFields) {
                let ucfirst = function(str) { return str.charAt(0).toUpperCase() + str.slice(1); };
                let query = identifier + ' { type ';

                if(typeof field.settings.on !== 'object') {
                    throw 'Field "' + identifier + '" of type "variants" needs an "on" setting with a query for each variant.';
                }

                if(Object.keys(field.settings.on).length > 0) {
                    query += ', ';
                }

                Object.keys(field.settings.on).forEach((variant) => {
                    query += '... on ' + field.settings.variant_schema_types[variant] + '{';
                    query += Object.keys(field.settings.on[variant]).map((videntifier) => {
                        return $uniteCMSViewFields.resolveFieldQueryFunction(field.settings.on[variant][videntifier].type)(
                            videntifier,
                            field.settings.on[variant][videntifier],
                            $uniteCMSViewFields
                        );
                    }).join(', ');
                    query += ' }';
                });

                return query + ' }';
            },
        },
        computed: {
        },
    }
</script>

<style lang="scss">
    .view-field-variants {
        display: flex;
        align-items: center;

        > * {
            margin-right: 10px;
            flex-basis: 0 !important;

            &:last-child {
                margin-right: 0;
            }
        }
    }
</style>