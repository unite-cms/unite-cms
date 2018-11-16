<template>
    <div class="view-field view-field-reference">
        <component v-if="value" v-for="(v,identifier) in settings.fields"
                   :key="identifier"
                   :is="$uniteCMSViewFields.resolve(v.type)"
                   :type="v.type"
                   :identifier="identifier"
                   label=""
                   :settings="v.settings"
                   :row="value"></component>
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
