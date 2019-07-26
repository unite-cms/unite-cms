<template>
    <span :data-type="field.type" class="view-field view-field-fallback">{{ typeof value === 'undefined' ? 'Unknown' : value }}</span>
</template>

<script>
    import BaseField from '../Base/AbstractRowField';

    export default {
        extends: BaseField
    }
</script>

<style scoped>
</style>
