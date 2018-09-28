<template>
    <span :data-name="name" :class="'view-field-' + type">
        <template v-if="type === 'text'">{{ value }}</template>
        <template v-else-if="type === 'checkbox'" v-html="value ? feather.icon['check-circle'].toSvg() : ''"></template>
        <template v-else>{{ value }}</template>
    </span>
</template>

<script>

    import feather from 'feather-icons';

    export default {
        data() {
            return {
                feather: feather
            };
        },
        props: ['name', 'value', 'type']
    }
</script>

<style scoped>

</style>