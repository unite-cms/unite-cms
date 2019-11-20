<template>
  <div>
    <template v-for="value in values">
      <span class="uk-label uk-label-muted">{{ label(value) }}</span>
      <br v-if="!isLastValue(value)" />
    </template>
  </div>
</template>
<script>
  import _abstract from "./_abstract";
  export default {
      extends: _abstract,
      methods: {
          label(value) {

              if(!value) {
                  return null;
              }

              let enumType = this.$unite.getRawType(this.field.returnType);

              if(!enumType) {
                  return null;
              }

              let labels = enumType.enumValues.filter((val) => {
                  return val.name === value;
              }).map((val) => {
                  return val.description || val.name;
              });

              return labels.length > 0 ? labels[0] : value;
          }
      }
  }
</script>
