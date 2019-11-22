<script>
  import Text from "./Text";
  export default {

      // Static query methods for unite system.
      queryData(field) { return field.id },
      normalizeData(inputData, field) { return inputData; },

      // Vue properties for this component.
      extends: Text,
      computed: {
          inputType() {
              return 'email';
          }
      },
  }
</script>
