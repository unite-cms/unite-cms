<template>
    <div :style="style" class="view-field view-field-collection">
        <div class="view-field-collection-row" v-for="(c_row,index) in value" :key="index" v-if="limitCollectionRows(index, settings)">
            <component v-for="(v,identifier) in settings.fields"
                       :key="identifier + index"
                       :is="$uniteCMSViewFields.resolve(v.type)"
                       :type="v.type"
                       :identifier="identifier"
                       label=""
                       :settings="v.settings"
                       :row="c_row"></component>
        </div>
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
                return identifier + ' { ' + Object.keys(field.settings.fields).map((identifier) => {
                    return $uniteCMSViewFields.resolveFieldQueryFunction(field.settings.fields[identifier].type)(
                        identifier,
                        field.settings.fields[identifier],
                        $uniteCMSViewFields
                    );
                }).join(', ') + ' }';
            },
        },
        computed: {
            limitCollectionRows() {
                return (i, s) => ("max_display_rows" in s) ? i < s.max_display_rows : true;
            },
        },
    }
</script>

<style scoped lang="scss">
    .view-field-collection-row {
        &:not(:last-child) {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding-bottom: 3px;
            margin-bottom: 3px;
        }
    }
</style>
