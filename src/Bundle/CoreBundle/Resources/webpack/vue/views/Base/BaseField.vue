<template>
    <div :style="style" class="uk-alert-warning" uk-alert>Abstract base field. Please implement a custom template in your view. </div>
</template>

<script>
    export default {
        data(){
            return {
                width: 0,
                minWidth: this.initialMinWidth || 50,
            }
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
        mounted() {
            this.$on('minWidthChanged', (minWidth) => {
                this.minWidth = minWidth;
            });
        },
        updated() {
            this.width = this.$el.childElementCount > 0 ? this.$el.children[0].offsetWidth : this.$el.offsetWidth;
        },
        watch: {
            width(width, oldWidth) {
                this.$emit('resized', {
                    identifier: this.identifier,
                    width: width
                });
            }
        },
        computed: {
            style() {
                return {
                    'flex-basis': (this.minWidth + 10) + 'px'
                }
            },

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
                        return resolveValue(parts.join('{'), row[root]);
                    }
                };
                return resolveValue(this.fieldQuery(this.identifier), this.row);
            },
        },
        props: ['identifier', 'label', 'settings', 'row', 'type', 'initialMinWidth'],
    }
</script>

<style scoped>

</style>
