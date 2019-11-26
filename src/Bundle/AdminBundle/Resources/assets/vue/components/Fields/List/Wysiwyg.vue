<template>
  <div>
    <div v-for="value in values" :class="{'uk-margin-small-bottom': !isLastValue(value)}" v-html="value"></div>
  </div>
</template>
<script>
  import _abstract from "./_abstract";
  export default {
      extends: _abstract
  }
</script>
