<template>
    <div class="uk-flex-1 uk-flex uk-flex-middle">
        <form class="uk-search uk-search-default uk-width-expand" v-if="filterRules.length > 0">
            <label for="view-filter-search" class="uk-icon uk-search-icon"><icon name="search" /></label>
            <input id="view-filter-search" class="uk-search-input" type="search" :placeholder="$t(advancedFilter ? 'content.list.search.placeholder_filter' : 'content.list.search.placeholder', { count: value.children ? value.children.length : 0 })" v-model="searchInput" @keyup="onSearchChange">
            <button class="uk-search-icon-flip uk-icon uk-search-icon" :class="{ 'uk-text-danger': hasFilterRules }" @click.prevent="showFilters = true"><icon name="sliders" /></button>
        </form>
        <modal v-if="showFilters" @hide="showFilters = false" :title="$t('content.list.search.filters.title')">
            <filter-rule :fields="filterRules" :value="value" @input="onFilterChange" />
        </modal>
    </div>
</template>
<script>

    import Icon from '../../components/Icon';
    import Modal from "../Modal";
    import FilterRule from "./_filterRule";

    export default {
        components: {FilterRule, Icon, Modal},
        data() {
            return {
                searchInput: null,
                advancedFilter: false,
                showFilters: false
            }
        },
        props: {
            value: Object,
            view: Object,
        },
        watch: {
            value(val) {
                if(!this.hasFilterRules) {
                    this.advancedFilter = false;
                    this.searchInput = null;
                }
            }
        },
        computed: {
            hasFilterRules() {
                return Object.keys(this.value).length > 0;
            },
            filterableFields() {
                return this.view.fields.filter((field) => {
                    let component = this.$unite.getListFieldType(field);
                    return component.filter ? !!component.filter(field, this.view, this.$unite) : false;
                })
            },
            searchableFields() {
                return this.filterableFields.filter((field) => {
                    let component = this.$unite.getListFieldType(field);
                    return component.filter ? !!component.filter(field, this.view, this.$unite).searchable : false;
                });
            },
            filterRules() {
                return this.filterableFields.map((field) => {
                    let component = this.$unite.getListFieldType(field);
                    return component.filter(field, this.view, this.$unite);
                });
            },
            filterLabels() {
                return {
                    matchType: this.$t('content.list.search.filters.matchType'),
                    matchTypes: [
                        {"id": "AND", "label": this.$t('content.list.search.filters.matchTypes.AND')},
                        {"id": "OR", "label": this.$t('content.list.search.filters.matchTypes.OR')}
                    ],
                    "addRule": this.$t('content.list.search.filters.addRule'),
                    "removeRule": this.$t('content.list.search.filters.removeRule'),
                    "addGroup": this.$t('content.list.search.filters.addGroup'),
                    "removeGroup": this.$t('content.list.search.filters.removeGroup'),
                    "textInputPlaceholder": this.$t('content.list.search.filters.textInputPlaceholder'),
                };
            }
        },
        methods: {
            onSearchChange(event) {
                this.advancedFilter = false;
                let value = event.target.value;
                this.$emit('input', !value ? {} : {
                    OR: this.searchableFields.map((field) => {
                        return {
                            field: field.id,
                            operator: 'CONTAINS',
                            value: `%${value}%`
                        };
                    }),
                });
            },
            onFilterChange(filter) {
                this.searchInput = null;
                this.advancedFilter = true;
                this.$emit('input', filter);
            }
        }
    }
</script>
