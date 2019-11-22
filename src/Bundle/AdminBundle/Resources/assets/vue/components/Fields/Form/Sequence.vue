<template>
  <form-row :domID="domID" :field="field">
    <input v-for="value in (values.length > 0 ? values : [''])" disabled type="text" class="uk-input" :id="domID" :value="value" />
  </form-row>
</template>
<script>
  import _abstract from "./_abstract";
  import FormRow from './_formRow';

  export default {

      // Static query methods for unite system.
      queryData(field) { return field.id },
      normalizeData(inputData, field) { return inputData; },

      // Vue properties for this component.
      extends: _abstract,
      components: { FormRow },
      data() {
          return {
              internalVal: null,
          }
      },
      watch: {
          val: {
              deep: true,
              handler(value) {
                  if(value) {
                      this.internalVal = value;
                  }
                  this.$emit('input', undefined);
              }
          }
      },
      computed: {
          values() {
              if(!this.internalVal) {
                  return [];
              }
              return this.field.list_of ? this.internalVal : [this.internalVal]
          },
      }
  }
</script>
