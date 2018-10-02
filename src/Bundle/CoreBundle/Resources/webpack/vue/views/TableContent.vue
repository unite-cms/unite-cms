<template>
    <div>
        <div :key="row.id" v-for="row in rows">
            <component v-for="(field,identifier) in fields"
                :key="identifier"
                :is="$uniteCMSViewFields.resolve(field.type)"
                :identifier="identifier"
                :label="field.label"
                :settings="field.settings"
                :row="row"></component>
        </div>
    </div>
</template>

<script>

    import BaseViewContent from './Base/BaseViewContent.vue';

    export default {
        extends: BaseViewContent,
        data() {
            return {};
        }
    }
</script>

<style scoped>

</style>
