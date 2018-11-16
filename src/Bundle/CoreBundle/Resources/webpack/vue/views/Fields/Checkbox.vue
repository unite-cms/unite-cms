<template>
    <div class="view-field view-field-checkbox fixed-width">
        <div v-if="value" v-html="feather.icons['check-circle'].toSvg({ width: 16, height: 16 })"></div>
        <div v-else></div>
    </div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';
    import feather from 'feather-icons';

    export default {
        extends: BaseField,
        data() {
            return {
                feather: feather
            }
        }

    }
</script>

<style scoped lang="scss">
    .view-field-checkbox {
        text-align: center;

        div {
            display: inline-block;
        }
    }
</style>