<template>
    <a v-if="!sortable" href="#" v-on:click.prevent="updateSort">
        {{ field.label }}
        <span v-if="sort" v-html="feather.icons[sort === 'ASC' ? 'arrow-down' : 'arrow-up'].toSvg({width: 16, height: 16})"></span>
    </a>
    <span v-else>
        {{ field.label }}
        <span v-if="sort" v-html="feather.icons[sort === 'ASC' ? 'arrow-down' : 'arrow-up'].toSvg({width: 16, height: 16})"></span>
    </span>
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
        },
        computed: {
            sort() {
                if(this.config.sort.field !== this.field.identifier) {
                    return null;
                }
                return this.config.sort.asc ? 'ASC' : 'DESC';
            },
            sortable() {
                return false;
            }
        },
        methods: {
            updateSort() {
                if(!this.sort) {
                    this.config.sort.field = this.field.identifier;
                    this.config.sort.asc = true;
                } else {
                    this.config.sort.asc = !this.config.sort.asc;
                }
            }
        }
    }
</script>

<style scoped>

</style>