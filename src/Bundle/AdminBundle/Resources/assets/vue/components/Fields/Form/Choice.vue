<template>
  <div class="uk-margin">
    <label class="uk-form-label" :for="domID">{{ name }}</label>
    <div class="uk-form-controls">
      <select class="uk-select" :id="domID" v-model="val">
        <option v-for="option in options" :value="option.value">{{ option.label }}</option>
      </select>
    </div>
  </div>
</template>
<script>
  import _abstract from "./_abstract";

  export default {
      extends: _abstract,
      queryData(field) { return field.id },
      normalizeData(inputData, field) { return inputData; },
      computed: {
          options() {
              let enumType = this.$unite.getRawType(this.field.rawField.type.name);

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
