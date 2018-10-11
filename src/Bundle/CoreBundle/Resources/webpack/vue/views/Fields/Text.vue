<template>
    <div class="view-field view-field-text"><p>{{ value }}</p></div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField
    }
</script>

<style scoped>
    .view-field-text {
        white-space: nowrap;
    }
</style>
