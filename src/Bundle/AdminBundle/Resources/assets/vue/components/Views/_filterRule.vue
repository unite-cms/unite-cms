<template>
    <div class="uk-width-expand uk-padding-small">
        <div class="uk-grid-small" uk-grid>
            <div class="uk-width-1-6">
                <select class="uk-select" v-model="filter.field">
                    <option :value="null" disabled>{{ $t('content.list.filter.field') }}</option>
                    <option v-for="field in fields" :value="field.id">{{ field.label }}</option>
                </select>
            </div>
            <div class="uk-width-1-6">
                <select class="uk-select" v-model="filter.operator">
                    <option :value="null" disabled>{{ $t('content.list.filter.operator') }}</option>
                    <option v-for="operator in operators" :value="operator.id">{{ operator.label }}</option>
                </select>
            </div>
            <div class="uk-width-auto">
                <input class="uk-input" type="text" :placeholder="$t('content.list.filter.textValuePlaceholder')" v-model="filter.value" />
            </div>
        </div>
        <div class="uk-margin uk-margin-small-top">
            <a class="uk-button uk-button-text uk-margin-small-right" @click.prevent="addFilter(filter.AND)">
                <icon name="plus-circle" class="fix-line-height uk-text-success" />
                {{ $t('content.list.filter.AND') }}
            </a>
            <a class="uk-button uk-button-text" @click.prevent="addFilter(filter.OR)">
                <icon name="plus-circle" class="fix-line-height uk-text-success" />
                {{ $t('content.list.filter.OR') }}
            </a>
        </div>
        <div class="uk-placeholder uk-padding-small" v-if="filter.AND && filter.AND.length > 0">
            <h5 class="uk-text-meta">{{ $t('content.list.filter.AND') }}</h5>
            <div v-for="(subFilter, delta) in filter.AND" class="uk-flex uk-flex-middle">
                <filter-rule :fields="fields" :key="'AND_' + delta" v-model="filter.AND[delta]" />
                <button class="uk-icon uk-text-danger" @click.prevent="deleteFilter(filter.AND, delta)"><icon name="minus-circle" /></button>
            </div>
        </div>
        <div class="uk-placeholder uk-padding-small" v-if="filter.OR && filter.OR.length > 0">
            <h5 class="uk-text-meta">{{ $t('content.list.filter.OR') }}</h5>
            <div v-for="(subFilter, delta) in filter.OR" class="uk-flex uk-flex-middle">
                <filter-rule :fields="fields" :key="'AND_' + delta" v-model="filter.OR[delta]" />
                <button class="uk-icon uk-text-danger" @click.prevent="deleteFilter(filter.OR, delta)"><icon name="minus-circle" /></button>
            </div>
        </div>
    </div>
</template>
<script>

    import Icon from '../../components/Icon';

    export default {
        components: {Icon},
        name: 'filterRule',
        data() {
            return {
                filter: Object.assign({
                    field: null,
                    operator: null,
                    cast: null,
                    AND: [],
                    OR: []
                }, this.value),
            };
        },
        watch: {
            filter: {
                deep: true,
                handler(filter) { this.$emit('input', filter); }
            }
        },
        props: {
            value: Object,
            fields: Array,
        },
        computed: {
            operators() {
                return this.$unite.getRawType('OPERATOR').enumValues.map((value) => {
                    return {
                        id: value.name,
                        label: value.description || value.name,
                    }
                });
            }
        },
        methods: {
            addFilter(group) {
                group.push({});
            },
            deleteFilter(group, delta) {
                group.splice(delta, 1);
            },
        }
    }
</script>
