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
                return identifier;
            },
            calcWidth() {
                this.width = this.$el.childElementCount > 0 ? this.$el.children[0].offsetWidth : this.$el.offsetWidth;
            }
        },
        mounted() {
            this.calcWidth();
            this.$on('minWidthChanged', (minWidth) => {
                this.minWidth = minWidth;
            });
        },
        watch: {
            row: {
                handler() {
                    this.$nextTick(() => {
                        this.calcWidth();
                    });
                },
                deep: true,
            },
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
                return this.row[this.identifier];
            },
        },
        props: ['identifier', 'label', 'settings', 'row', 'type', 'initialMinWidth'],
    }
</script>

<style scoped>

</style>
