<template>
    <div :style="style" class="view-field view-field-variants">
        <div class="uk-label" v-if="settings.title">{{ settings.variant_titles[value.type] }}</div>
        <component v-if="value" :key="identifier" v-for="(v,identifier) in settings.on[value.type]"
                   :is="$uniteCMSViewFields.resolve(v.type)"
                   :type="v.type"
                   :identifier="identifier"
                   label=""
                   :settings="v.settings"
                   :row="value"></component>
    </div>
</template>

<script>
    import BaseField from '../../../../../../CoreBundle/Resources/webpack/vue/views/Base/BaseField.vue';

    export default {
        extends: BaseField,
        methods: {

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