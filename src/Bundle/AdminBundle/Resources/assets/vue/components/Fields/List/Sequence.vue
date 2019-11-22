<template>
  <div>
    <template v-for="value in values">
      <span class="uk-label uk-label-muted">{{ value }}</span>
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
