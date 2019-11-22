<template>
  <div>
    <template v-for="value in values">
      <span class="uk-text-nowrap">{{ $d(new Date(value), 'date') }}</span>
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
