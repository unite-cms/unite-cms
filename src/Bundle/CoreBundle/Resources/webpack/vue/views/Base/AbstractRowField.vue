<template>
    <div>Row field</div>
</template>

<script>
    export default {
        props: {
            config: Object,
            field: Object,
            row: Object,
        },
        methods: {
            /**
             * Each field must implement a field Query method that gets called to alter the fetch command. The default
             * implementation just adds recursively all keys (separated by ".") to the field query.
             *
             * @param identifier, the specified identifier
             * @param field, the field object config
             */
            fieldQuery(identifier, field) {
                return identifier;
            },

            /**
             * Each field must implement a filter Query method that gets called to alter the search command. The default
             * implementation just adds an ILIKE filter. If null is returned, this field is not searchable.
             *
             * @param identifier, the specified identifier
             * @param field, the field object config
             */
            filterQuery(identifier, field) {
                return {
                    field: identifier,
                    operator: 'ILIKE',
                    value: (value) => { return '%' + value + '%' }
                };
            }
        },
        computed: {

            /**
             * Each field must implement a value method that gets called to get the data from the API result set.
             * The default implementation just uses the identifier to look for the data in the (possible nested) result.
             */
            value() {
                return this.row.get(this.field.identifier);
            },
        },
    }
</script>

<style scoped>

</style>