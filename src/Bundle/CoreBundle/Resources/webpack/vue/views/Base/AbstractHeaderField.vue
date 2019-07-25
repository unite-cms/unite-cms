<template>
    <span v-html="field.icon ? feather.icons[field.icon].toSvg({width: 16, height: 16}) : field.label"></span>
</template>

<script>

    import feather from 'feather-icons';

    export default {
        data() {
            return {
                feather: feather,
            }
        },
        props: {
            config: Object,
            field: Object,
        }
    }
</script>

<style scoped>

</style>