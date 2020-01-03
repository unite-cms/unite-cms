<template>
    <select class="uk-select" :value="value" @change="change">
        <option :value="null" disabled>{{ $t('content.list.filter.placeholder') }}</option>
        <option :value="option.value" v-for="option in options">{{ option.label }}</option>
    </select>
</template>

<script>
    export default {
        props: {
            value: String,
            options: Array
        },
        methods: {
            change(event) {
                this.$emit('input', event.target.value);
            }
        }
    }
</script>
