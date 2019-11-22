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
  import { getAdminViewByType } from "../../../plugins/unite";

  export default {
      extends: _abstract,
      computed: {
          referencedView() {
              return getAdminViewByType(this.$unite, this.field.returnType);
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
