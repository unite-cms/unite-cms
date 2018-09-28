<template>
    <div>
        <div v-for="row in rows">
            <view-field v-for="field in Object.keys(row)" :name="field" :value="row[field]"></view-field>
        </div>
    </div>
</template>

<script>

    import BaseViewContent from './Base/BaseViewContent.vue';

    export default {
        extends: BaseViewContent
    }
</script>

<style scoped>

</style>