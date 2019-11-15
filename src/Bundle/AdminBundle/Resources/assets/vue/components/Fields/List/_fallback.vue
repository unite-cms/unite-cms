<template>
  <div class="uk-alert-warning" uk-alert>{{ $t('content.list.field.fallback_warning', field) }}</div>
</template>
<script>
  import _abstract from "./_abstract";

  export default {
      extends: _abstract
  }
</script>
<style scoped lang="scss">
  .uk-alert {
    padding: 5px 10px;
    font-size: 0.8rem;
  }
</style>
