<template>
    <a v-if="sortable" href="#" v-on:click.prevent="updateSort">
        {{ field.label }}
        <span v-if="sort" v-html="feather.icons[sort === 'ASC' ? 'arrow-up' : 'arrow-down'].toSvg({width: 16, height: 16})"></span>
    </a>
    <span v-else>
        {{ field.label }}
        <span v-if="sort" v-html="feather.icons[sort === 'ASC' ? 'arrow-up' : 'arrow-down'].toSvg({width: 16, height: 16})"></span>
    </span>
</template>

<script>
    import AbstractHeaderField from './AbstractHeaderField';

    export default {
        extends: AbstractHeaderField,
        computed: {
            sort() {
                if(this.config.sort.field !== this.field.identifier) {
                    return null;
                }
                return this.config.sort.asc ? 'ASC' : 'DESC';
            },
            sortable() {

                // If the view is sortable by drag & drop, we do not allow to 
                // sort by clicking on column header.
                return !this.config.sort.sortable;
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