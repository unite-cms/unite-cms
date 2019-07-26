<template>
    <span class="view-field view-field-reference">
        <span v-for="(v,identifier) in settings.fields" :key="identifier">
            <component v-if="value"
                   :is="$uniteCMSViewFields.resolve(v.type)"
                   :type="v.type"
                   :identifier="identifier"
                   label=""
                   :settings="v.settings"
                   :row="value"></component>
        </span>
    </span>
</template>

<script>
    import BaseField from '../Base/AbstractRowField';

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
        },
    }
</script>
