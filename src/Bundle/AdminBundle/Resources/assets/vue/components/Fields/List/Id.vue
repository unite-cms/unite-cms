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
      extends: _abstract
  }
</script>
<style scoped lang="scss">
  .uk-text-truncate {
    width: 100px;
  }
</style>
