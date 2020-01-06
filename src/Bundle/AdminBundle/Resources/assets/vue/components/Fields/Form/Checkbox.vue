<template>
    <form-row :domID="domID" :field="field" :alerts="violations" :show-label="false">
        <multi-field :field="field" :val="val" @addRow="val.push(false)" @removeRow="removeByKey" v-slot:default="multiProps">
            <label :for="domID">
                <input class="uk-checkbox" :required="field.required" :id="domID" type="checkbox" :checked="values[multiProps.rowKey || 0]" :value="values[multiProps.rowKey || 0]" @input="setValue(arguments, multiProps.rowKey)" />
                {{ name }}
            </label>
        </multi-field>
    </form-row>
</template>
<script>
    import _abstract from "./_abstract";
    import FormRow from './_formRow';
    import MultiField from './_multiField';

    export default {

        // Static query methods for unite system.
        queryData(field, unite, depth) { return field.id },
        normalizeQueryData(queryData, field, unite) { return queryData; },
        normalizeMutationData(formData, field, unite) { return formData; },

        // Vue properties for this component.
        extends: _abstract,
        components: { MultiField, FormRow },
        computed: {
            name() {
                return this.field.name.slice(0, 1).toUpperCase() + this.field.name.slice(1);
            }
        }
    }
</script>
