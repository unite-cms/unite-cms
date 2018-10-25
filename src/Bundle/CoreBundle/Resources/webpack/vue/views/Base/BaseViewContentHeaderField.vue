<template>
    <div :style="style" :class="{ 'fixed-width': fixedWidth }" v-if="type !== 'sortindex' || isSortable">

        <span v-if="type === 'sortindex' && isSortable"><span v-if="sort.field === identifier" v-html="feather.icons[sort.asc ? 'arrow-down' : 'arrow-up'].toSvg({width: 16, height: 16})"></span></span>

        <a v-else-if="!isSortable" href="#" v-on:click.prevent="setSort(identifier)">
            {{ label }}
            <span v-if="sort.field === identifier" v-html="feather.icons[sort.asc ? 'arrow-down' : 'arrow-up'].toSvg({width: 16, height: 16})"></span>
        </a>

        <span v-else>
            {{ label }}
            <span v-if="sort.field === identifier" v-html="feather.icons[sort.asc ? 'arrow-down' : 'arrow-up'].toSvg({width: 16, height: 16})"></span>
        </span>
    </div>
</template>

<script>

    import feather from 'feather-icons';

    export default {
        data(){
            return {
                width: 0,
                minWidth: this.initialMinWidth || 50,
                feather: feather,
            }
        },
        mounted() {
            this.calcWidth();
            this.$on('minWidthChanged', (minWidth) => {
                this.minWidth = minWidth;
            });
        },
        watch: {
            width(width) {
                this.$emit('resized', {
                    identifier: this.identifier,
                    width: width
                });
            }
        },
        computed: {
            style() {
                return {
                    'flex-basis': (this.minWidth + (this.identifier === '_actions' ? 0 : 10)) + 'px'
                }
            },
        },
        methods: {
            setSort(identifier) {
                this.$emit('sortChanged', identifier);
            },
            calcWidth() {
                this.width = this.$el.childElementCount > 0 ? this.$el.children[0].offsetWidth : this.$el.offsetWidth;
            }
        },
        props: ['identifier', 'label', 'isSortable', 'type', 'sort', 'initialMinWidth', 'fixedWidth'],
    }
</script>

<style scoped>

</style>
