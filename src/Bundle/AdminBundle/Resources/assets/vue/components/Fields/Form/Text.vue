<template>
  <div class="uk-margin">
    <label class="uk-form-label" :for="domID">{{ name }}</label>
    <div class="uk-form-controls">
      <input class="uk-input" :id="domID" type="text" v-model="val">
    </div>
  </div>
</template>
<script>
  import _abstract from "./_abstract";

  export default {
      extends: _abstract,
      queryData(field) { return field.id },
      normalizeData(inputData, field) { return inputData; }
  }
</script>
