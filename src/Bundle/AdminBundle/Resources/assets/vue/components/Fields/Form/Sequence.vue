<template>
  <form-row :domID="domID" :field="field">
    <input v-for="value in (values.length > 0 ? values : [''])" disabled type="text" class="uk-input" :id="domID" :value="value" />
  </form-row>
</template>
<script>
  import _abstractReadOnly from "./_abstractReadOnly";
  import FormRow from './_formRow';

  export default {

      // Static query methods for unite system.
      queryData(field) { return field.id },
      normalizeData(inputData, field) { return inputData; },

      // Vue properties for this component.
      extends: _abstractReadOnly,
      components: { FormRow },
  }
</script>
