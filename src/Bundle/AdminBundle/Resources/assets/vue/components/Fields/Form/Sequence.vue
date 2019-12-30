<template>
  <form-row :domID="domID" :field="modField" :alerts="violations">
    <multi-field :field="field" :val="val" @addRow="val.push('')" @removeRow="removeByKey" v-slot:default="multiProps">
      <input v-if="values[multiProps.rowKey || 0]" class="uk-input" disabled :id="domID" type="text" :value="values[multiProps.rowKey || 0]" />
      <div v-else class="uk-placeholder uk-padding-small uk-text-meta">{{ $t('field.sequence.no_value_message') }}</div>
    </multi-field>
  </form-row>
</template>
<script>
  import FormRow from './_formRow';
  import MultiField from './_multiField';
  import _abstract from "./_abstract";

  export default {

      // Static query methods for unite system.
      queryData(field, unite, depth) { return field.id },
      normalizeQueryData(queryData, field, unite) { return queryData; },
      normalizeMutationData(formData, field, unite) { return undefined; },

      // Vue properties for this component.
      extends: _abstract,
      components: { FormRow, MultiField },
      computed: {
        modField() {
          return Object.assign(this.field, { required: false });
        }
      }
  }
</script>
