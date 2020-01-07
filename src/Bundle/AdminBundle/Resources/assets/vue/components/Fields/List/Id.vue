<template>
  <div>
    <template v-for="value in values">
      <div class="uk-text-meta uk-text-truncate" :title="value" uk-tooltip>{{ value }}</div>
      <br v-if="!isLastValue(value)" />
    </template>
  </div>
</template>
<script>
  import _abstract from "./_abstract";
  export default {
      extends: _abstract,

    // static filter method
    filter(field, view, unite) { return _abstract.filter(field, view, unite); },
  }
</script>
<style scoped lang="scss">
  .uk-text-truncate {
    width: 100px;
  }
</style>
