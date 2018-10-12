<template>
    <div :style="style" class="view-field view-field-text"><p>{{ value }}</p></div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField
    }
</script>

<style scoped lang="scss">
    .view-field-text {
        white-space: nowrap;

        p {
            display: inline-block;
        }
    }
</style>
