<template>
    <div class="view-field view-field-collection">
        <div class="view-field-collection-row" v-for="(c_row,index) in limitCollectionRows" :key="index">
            <component v-for="(v,identifier) in field.settings.fields"
                       :key="identifier + index"
                       :is="$uniteCMSViewFields.resolve(v.type)"
                       :config="config"
                       :field="Object.assign({}, v, { identifier: identifier, label: '' })"
                       :row="createRow(c_row)"></component>
        </div>
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
                return this.field.settings.max_display_rows ? this.value.slice(0, this.field.settings.max_display_rows) : this.value;
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
