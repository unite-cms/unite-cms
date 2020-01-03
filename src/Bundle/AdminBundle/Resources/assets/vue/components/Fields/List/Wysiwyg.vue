<template>
  <div>
    <div v-for="value in values" :class="{'uk-margin-small-bottom': !isLastValue(value)}" v-html="value"></div>
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
