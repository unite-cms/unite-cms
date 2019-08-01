<template>
    <span class="view-field view-field-reference">
        <span v-for="(v,identifier) in field.settings.fields" :key="field.identifier">
            <component v-if="value"
                   :is="$uniteCMSViewFields.resolve(v.type)"
                   :config="config"
                   :field="Object.assign({}, v, { identifier: identifier, label: '' })"
                   :row="createRow(value)"></component>
        </span>
    </span>
</template>

<script>
    import BaseField from '../Base/AbstractRowField';
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
        },
    }
</script>
