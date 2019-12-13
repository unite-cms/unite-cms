<script>
  import Text from "./Text";
  export default {

      // Static query methods for unite system.
      queryData(field, unite, depth) { return field.id },
      normalizeQueryData(queryData, field, unite) { return queryData; },
      normalizeMutationData(formData, field, unite) { return formData; },

      // Vue properties for this component.
      extends: Text,
      computed: {
          inputType() {
              return 'number';
          }
      },
  }
</script>
