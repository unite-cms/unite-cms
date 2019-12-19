<template>
  <div>
    <div v-for="value in values">
      <template v-for="nestedField in nestedFields(value)">
        <component :is="$unite.getListFieldType(nestedField)" :field="nestedField" :row="value" />
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
          }
      },
      methods: {
          nestedFields(value) {
              return Object.keys(value).map((key) => {
                  return key === '__typename' ? null : this.fieldComponent(key, value.__typename)
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

          referencedRowView(type) {

              if(!this.referencedView) {
                  return null;
              }

              if(this.referencedView.category === 'union') {
                  let views = this.referencedView.possibleViews.filter((view) => { return view.type === type; });
                  return views.length > 0 ? views[0] : null;
              }

              return this.referencedView;
          },

          fieldComponent(key, type) {

              if(!this.referencedView) {
                  return this.fallbackField(key);
              }
              let referencedField = this.referencedRowView(type).fields.filter((field) => {
                  return field.id === key;
              });

              return referencedField.length > 0 ? referencedField[0] : this.fallbackField(key);
          }
      }
  }
</script>
