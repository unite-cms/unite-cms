<template>
<div>
    <span @click="$emit('toggle', row)" v-html="icon"></span>
</div>
</template>

<script>

    import BaseField from './AbstractRowField';
    import feather from 'feather-icons';

    export default {
        FIELD_WIDTH_COLLAPSED: true,
        extends: BaseField,
        props: {
            open: Boolean,
        },
        computed: {
            icon() {
                return feather.icons[this.open ? 'minus-square' : 'plus-square'].toSvg({width: 16, height: 16})
            }
        }
    }
</script>
<style lang="scss" scoped>
    span {
        cursor: pointer;
        height: 30px;
        width: 40px;
        line-height: 30px;
        display: block;
        text-align: center;
        margin-left: -13px;
    }
</style>