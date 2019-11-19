<template>
  <span class="uk-label uk-label-muted">{{ label(row[field.id]) }}</span>
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

              let enumType = this.$unite.getRawType(this.field.rawField.type.name);

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
