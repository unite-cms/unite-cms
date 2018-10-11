<template>
    <div class="view-field view-field-id"><span class="uk-text-meta" :title="value" uk-tooltip>{{ row[identifier] ? '#' + value.split('-')[0] : '' }}</span></div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField
    }
</script>

<style scoped lang="scss">
    .view-field-id {
        display: inline-block;

        .uk-text-meta {
            white-space: nowrap;
            cursor: pointer;
        }
    }
</style>
