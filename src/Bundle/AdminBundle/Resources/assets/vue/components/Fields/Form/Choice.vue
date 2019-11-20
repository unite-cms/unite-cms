<template>
  <div class="uk-margin">
    <label class="uk-form-label" :for="domID">{{ name }}</label>
    <div class="uk-form-controls">
      <select v-if="!field.list_of" class="uk-select" :id="domID" v-model="val">
        <option v-for="option in options" :value="option.value">{{ option.label }}</option>
      </select>
      <template v-else>
        <template v-for="option in options">
          <label><input v-model="val" class="uk-checkbox" type="checkbox" :value="option.value"> {{ option.label }}</label><br>
        </template>
      </template>
      <p v-if="field.description" class="uk-text-meta uk-margin-small-top">{{ field.description }}</p>
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
