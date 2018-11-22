<template>
    <div class="uk-alert-warning" uk-alert>Abstract base field. Please implement a custom template in your view. </div>
</template>

<script>
    export default {
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
             * implementation just adds a LIKE filter. If null is returned, this field is not searchable.
             *
             * @param identifier, the specified identifier
             * @param field, the field object config
             */
            filterQuery(identifier, field) {
                return {
                    field: identifier,
                    operator: 'LIKE',
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
                return this.row[this.identifier];
            },
        },
        props: ['identifier', 'label', 'settings', 'row', 'type', 'sortable'],
    }
</script>