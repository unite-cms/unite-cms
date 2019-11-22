<template>
  <form-row :domID="domID" :field="field">
      <select :required="field.non_null" v-if="!field.list_of" class="uk-select" :id="domID" v-model="val">
        <option v-for="option in options" :value="option.value">{{ option.label }}</option>
      </select>
      <template v-else>
        <template v-for="option in options">
          <label><input v-model="val" class="uk-checkbox" type="checkbox" :value="option.value"> {{ option.label }}</label><br>
        </template>
      </template>
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
      components: { FormRow },
      extends: _abstract,
      computed: {
          options() {
              let enumType = this.$unite.getRawType(this.field.returnType);

              if(!enumType) {
                  return [];
              }

              return enumType.enumValues.map((val) => {
                  return {
                      value: val.name,
                      label: val.description || val.name,
                  }
              });
          }
      }
  }
</script>
