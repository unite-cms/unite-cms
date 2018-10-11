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
                let createNestedQuery = (identifier) => {
                    if(!identifier || identifier.length === 0) {
                        return;
                    }

                    let parts = identifier.split('.');
                    let root = parts.shift();
                    return root + ((parts.length > 0) ? ' {' + createNestedQuery(parts.join('.')) + '}' : '');
                };
                return createNestedQuery(identifier);
            },
        },
        computed: {
            /**
             * Each field must implement a value method that gets called to get the data from the API result set.
             * The default implementation just uses the identifier to look for the data in the (possible nested) result.
             */
            value() {
                let resolveValue = (identifier, row) => {
                    if(!identifier || identifier.length === 0) {
                        return row;
                    }

                    let parts = identifier.split('{');
                    let root = parts.shift();
                    root = root.replace(/\}/g, "").trim();

                    if(row[root]) {
                        return resolveValue(parts.join('}'), row[root]);
                    }
                };
                return resolveValue(this.fieldQuery(this.identifier), this.row);
            },
        },
        props: ['identifier', 'label', 'settings', 'row'],
    }
</script>

<style scoped>

</style>
