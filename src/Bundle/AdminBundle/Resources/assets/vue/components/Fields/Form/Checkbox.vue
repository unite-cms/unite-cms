<template>
  <form-row :domID="domID" :field="field" :alerts="violations">
    <multi-field :field="field" :val="val" @addRow="val.push(false)" @removeRow="removeByKey" v-slot:default="multiProps">
      <input class="uk-checkbox" :required="field.required" :id="domID" type="checkbox" :value="values[multiProps.rowKey || 0]" @input="setValue(arguments, multiProps.rowKey)" />
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
    }
</script>
