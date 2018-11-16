<template>
    <div :style="style" class="view-field view-field-reference-of">
        <div class="view-field-reference-of-row" v-for="(c_row,index) in value.result">
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
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField,
        methods: {

            /**
             * @inheritdoc
             */
            fieldQuery(identifier, field, $uniteCMSViewFields) {
                return identifier + ' { result { ' + Object.keys(field.settings.fields).map((identifier) => {
                    return $uniteCMSViewFields.resolveFieldQueryFunction(field.settings.fields[identifier].type)(
                        identifier,
                        field.settings.fields[identifier],
                        $uniteCMSViewFields
                    );
                }).join(', ') + ' } }';
            },
        },
        computed: {
        },
    }
</script>

<style scoped lang="scss">
    .view-field-reference-of-row {
        &:not(:last-child) {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding-bottom: 3px;
            margin-bottom: 3px;
        }
    }
</style>
