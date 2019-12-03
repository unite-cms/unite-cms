<template>
  <form-row :domID="domID" :field="field">
    <input v-for="value in (values.length > 0 ? values : [''])" disabled type="text" class="uk-input" :id="domID" :value="value" />
  </form-row>
</template>
<script>
  import FormRow from './_formRow';
  import _abstract from "./_abstract";

  export default {

      // Static query methods for unite system.
      queryData(field, unite, depth) { return field.id },
      normalizeQueryData(queryData, field, unite) { return queryData; },
      normalizeMutationData(formData, field, unite) { return undefined; },

      // Vue properties for this component.
      extends: _abstract,
      components: { FormRow },
  }
</script>
