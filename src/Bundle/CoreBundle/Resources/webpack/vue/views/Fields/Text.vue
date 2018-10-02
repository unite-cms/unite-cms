<template>
    <div class="view-field view-field-text"><p class="uk-text-truncate">{{ row[identifier] }}</p></div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField
    }
</script>

<style scoped>

</style>
