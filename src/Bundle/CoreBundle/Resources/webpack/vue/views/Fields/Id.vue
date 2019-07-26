<template>
    <span class="uk-text-meta" :title="value" uk-tooltip>{{ value ? '#' + value.split('-')[0] : '' }}</span>
</template>

<script>
    import BaseField from '../Base/AbstractRowField';
    export default {
        extends: BaseField,
    }
</script>

<style scoped lang="scss">
    .uk-text-meta {
        white-space: nowrap;
        cursor: pointer;
    }
</style>
