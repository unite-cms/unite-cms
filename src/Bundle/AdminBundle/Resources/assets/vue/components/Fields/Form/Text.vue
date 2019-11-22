<template>
  <form-row :domID="domID" :field="field">
    <multi-field :field="field" :val="val" @addRow="val.push('')" @removeRow="removeByKey" v-slot:default="multiProps">
      <input v-if="field.list_of" class="uk-input" :id="domID" :type="inputType" v-model="val[multiProps.rowKey]" />
      <input v-else class="uk-input" :id="domID" :type="inputType" v-model="val" />
    </multi-field>
  </form-row>
</template>
<script>
  import _abstract from "./_abstract";
  import FormRow from './_formRow';
  import MultiField from './_multiField';

  export default {

      // Static query methods for unite system.
      queryData(field) { return field.id },
      normalizeData(inputData, field) { return inputData; },

      // Vue properties for this component.
      extends: _abstract,
      components: { MultiField, FormRow },
      computed: {
          inputType() {
              return 'text';
          }
      },
  }
</script>
