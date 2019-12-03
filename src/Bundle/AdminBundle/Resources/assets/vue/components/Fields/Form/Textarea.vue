<template>
  <form-row :domID="domID" :field="field">
    <multi-field :field="field" :val="val" @addRow="val.push('')" @removeRow="removeByKey" v-slot:default="multiProps">
      <textarea class="uk-textarea" :required="field.non_null" :id="domID" :value="values[multiProps.rowKey || 0]" @input="setValue(arguments, multiProps.rowKey)"></textarea>
    </multi-field>
  </form-row>
</template>
<script>
  import _abstract from "./_abstract";
  import FormRow from './_formRow';
  import MultiField from './_multiField';
  import Textarea from "../List/Textarea";

  export default {

      // Static query methods for unite system.
      queryData(field, unite, depth) { return field.id },
      normalizeQueryData(queryData, field, unite) { return queryData; },
      normalizeMutationData(formData, field, unite) { return formData; },

      // Vue properties for this component.
      extends: _abstract,
      components: {Textarea, MultiField, FormRow },
  }
</script>
