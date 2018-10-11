<template>
    <div class="view-field view-field-fallback">{{ typeof row[identifier] === 'undefined' ? 'Unknown' : row[identifier] }}</div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField,
        methods: {
            /**
             * Each field must implement a fetch field method that gets called to alter the fetch command. The default
             * implementation just adds the key to the field query.
             *
             * @param identifier, the specified identifier
             * @param field, the field object config
             */
            fieldQuery(identifier, field) {
                return identifier
            },
        }
    }
</script>

<style scoped>
    .view-field-fallback {
        white-space: nowrap;
    }
</style>
