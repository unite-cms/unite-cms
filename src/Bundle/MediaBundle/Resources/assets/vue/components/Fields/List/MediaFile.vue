<template>
  <div>
    <div v-for="value in values" :class="{'uk-margin-small-bottom': !isLastValue(value)}">
      <pre>{{ value }}</pre>
    </div>
  </div>
</template>
<script>
  import _abstract from "../../../../../../../AdminBundle/Resources/assets/vue/components/Fields/List/_abstract";
  export default {
      extends: _abstract
  }
</script>
