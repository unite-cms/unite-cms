<template>
  <div>
    <template v-for="value in values">
      <icon :name="value ? 'check-circle' : 'x-circle'" :class="value ? 'uk-text-success' : 'uk-text-danger'" />
      <br v-if="!isLastValue(value)" />
    </template>
  </div>
</template>
<script>
  import _abstract from "./_abstract";
  import Icon from "../../Icon";

  export default {
      extends: _abstract,
      components: {Icon}
  }
</script>
