<template>
    <div class="uk-flex uk-flex-middle">
        <form class="uk-search uk-search-default">
            <a href="#" class="uk-search-icon-flip" uk-search-icon></a>
            <input class="uk-search-input" type="search" placeholder="" v-model="searchInput">
        </form>
        <button class="uk-button uk-button-light" v-if="hasFilter">
            <icon name="plus" />
        </button>
    </div>
</template>
<script>

    import Icon from '../../components/Icon';

    export default {
        components: {Icon},
        data() {
            return {
                searchInput: null,
                filter: this.value
            }
        },
        props: {
            value: Object
        },
        computed: {
            hasFilter() {
                return this.filter && Object.keys(this.filter).length > 0
            }
        },
        watch: {
            filter: {
                deep: true,
                handler(value) {
                    this.$emit('input', value);
                }
            },
            searchInput(value) {
                this.filter = {
                    field: "name",
                    operator: "CONTAINS",
                    value: '%' + value + '%'
                };
            }
        }
    }
</script>
