<template>
    <div :style="style" :data-type="type" class="view-field view-field-fallback">{{ typeof value === 'undefined' ? 'Unknown' : value }}</div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField
    }
</script>

<style scoped>
    .view-field-fallback {
        white-space: nowrap;
    }
</style>
