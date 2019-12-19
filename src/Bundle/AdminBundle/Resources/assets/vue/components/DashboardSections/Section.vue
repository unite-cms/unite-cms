<template>
    <div :class="classes">
        <div class="uk-card-default uk-card-body">
            <h2 class="uk-card-title">{{ compiledTextPattern(title) }}</h2>
            <slot></slot>
        </div>
        <div class="uk-card-footer" v-if="$slots.footer">
            <slot name="footer"></slot>
        </div>
    </div>
</template>

<script>
    import Mustache from 'mustache';

    export default {
        props: {
            title: String,
            width: {
                type: Array,
                default() { return ['1-1'] }
            },
            data: Object
        },
        computed: {
            classes() {
                return (this.width || ['1-1']).map(width => `uk-width-${width}`);
            },
        },
        methods: {
            compiledTextPattern(pattern) {
                return Mustache.render(pattern, this.data);
            }
        }
    }
</script>
