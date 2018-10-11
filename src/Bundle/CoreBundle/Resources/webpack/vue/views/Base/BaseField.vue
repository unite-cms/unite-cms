<template>
    <div class="uk-alert-warning" uk-alert>Abstract base field. Please implement a custom template in your view. </div>
</template>

<script>
    export default {
        data() {
            return {
                fieldConfig: Object.assign({}, this.settings, this.config)
            };
        },
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
        },
        props: ['identifier', 'label', 'config', 'settings', 'row'],
    }
</script>

<style scoped>

</style>
