<template>
  <div>
    <div v-for="value in values">
      <template v-for="nestedField in nestedFields(value)">
        <component :is="$unite.getListFieldType(nestedField.type)" :field="nestedField" :row="value" />
      </template>
    </div>
  </div>
</template>
<script>
  import _abstract from "./_abstract";
  export default {
      extends: _abstract,
      computed: {
          referencedView() {
              let referencedView = Object.values(this.$unite.adminViews).filter((view) => {
                  return view.type === this.field.returnType;
              });

              return referencedView.length > 0 ? referencedView[0] : null;
          },
      },
      methods: {
          nestedFields(value) {
              return Object.keys(value).map((key) => {
                  return key === '__typename' ? null : this.fieldComponent(key)
              }).filter((field) => { return !!field });
          },
          fallbackField(key) {
              let type = 'unknown';

              if(key === 'id') {
                  type = 'id';
              }

              return {
                  type: type,
                  id: key,
              };
          },
          fieldComponent(key) {

              if(!this.referencedView) {
                  return this.fallbackField(key);
              }

              let referencedField = this.referencedView.fields.filter((field) => {
                  return field.id === key;
              });

              return referencedField.length > 0 ? referencedField[0] : this.fallbackField(key);
          }
      }
  }
</script>
