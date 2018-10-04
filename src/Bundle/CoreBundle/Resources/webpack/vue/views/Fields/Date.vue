<template>
    <div class="view-field view-field-date"><time class="uk-text-meta" :title="row[identifier]|dateFull" uk-tooltip>{{ row[identifier]|dateFromNow }}</time></div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField
    }
</script>

<style scoped lang="scss">
    .view-field-date {
        display: inline-block;

        time {
            cursor: pointer;
            white-space: nowrap;
        }
    }
</style>
