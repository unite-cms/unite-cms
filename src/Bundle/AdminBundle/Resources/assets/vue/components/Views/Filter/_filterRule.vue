<template>
    <div class="uk-width-expand uk-placeholder uk-padding-small">

        <ul class="uk-subnav uk-subnav-divider" uk-margin>
            <li :class="{'uk-active': filter.field !== undefined}"><a href="#" @click.prevent="filter = { field: null, operator: null }">{{ $t('content.list.filter.filter') }}</a></li>
            <li :class="{'uk-active': filter.AND}"><a href="#" @click.prevent="filter = { AND: [] }">{{ $t('content.list.filter.AND') }}</a></li>
            <li :class="{'uk-active': filter.OR}"><a href="#" @click.prevent="filter = { OR: [] }">{{ $t('content.list.filter.OR') }}</a></li>
        </ul>

        <div v-if="filter.AND">
            <div v-for="(subFilter, delta) in filter.AND" class="uk-flex uk-flex-middle">
                <filter-rule :fields="fields" :key="'AND_' + delta" v-model="filter.AND[delta]" />
                <button class="uk-icon uk-text-danger uk-margin-small-left" @click.prevent="deleteFilter(filter.AND, delta)"><icon name="minus-circle" /></button>
            </div>
            <a class="uk-icon-button uk-button-light uk-icon-button-small" @click.prevent="addFilter(filter.AND)"><icon name="plus" /></a>
        </div>
        <div v-else-if="filter.OR">
            <div v-for="(subFilter, delta) in filter.OR" class="uk-flex uk-flex-middle">
                <filter-rule :fields="fields" :key="'AND_' + delta" v-model="filter.OR[delta]" />
                <button class="uk-icon uk-text-danger uk-margin-small-left" @click.prevent="deleteFilter(filter.OR, delta)"><icon name="minus-circle" /></button>
            </div>
            <a class="uk-icon-button uk-button-light uk-icon-button-small" @click.prevent="addFilter(filter.OR)"><icon name="plus" /></a>
        </div>
        <div v-else>
            <div class="uk-grid-small" uk-grid>
                <div class="uk-width-1-6">
                    <select class="uk-select" v-model="filter.field" @change="onFieldChange">
                        <option :value="null" disabled>{{ $t('content.list.filter.field') }}</option>
                        <option v-for="field in fields" :value="field.id">{{ field.label }}</option>
                    </select>
                </div>
                <div class="uk-width-1-6" v-if="selectedField">
                    <select class="uk-select" v-model="filter.operator">
                        <option :value="null" disabled>{{ $t('content.list.filter.operator') }}</option>
                        <option v-for="operator in operators(selectedField.operators)" :value="operator.id">{{ operator.label }}</option>
                    </select>
                </div>
                <div class="uk-width-2-3">
                    <component v-if="selectedField" :is="selectedField.input" v-bind="selectedField.inputProps" v-model="filter.value" />
                </div>
            </div>
        </div>
    </div>
</template>
<script>

    import Icon from '../../Icon';

    export default {
        components: {Icon},
        name: 'filterRule',
        data() {
            return {
                filter: this.value && Object.keys(this.value).length > 0 ? this.value : { field: null, operator: null },
            };
        },
        watch: {
            filter: {
                deep: true,
                handler(filter, oldFilter) {

                    if(this.selectedField) {
                        filter.cast = this.selectedField.cast;
                    }

                    this.$emit('input', filter);
                }
            }
        },
        props: {
            value: Object,
            fields: Array,
        },
        computed: {
            selectedField() {
                if(!this.filter.field) {
                    return null;
                }
                let fields = this.fields.filter((field) => { return field.id === this.filter.field });
                return fields.length > 0 ? fields[0] : null;
            }
        },
        methods: {
            addFilter(group) {
                group.push({});
            },
            deleteFilter(group, delta) {
                group.splice(delta, 1);
            },
            operators(operators) {
                return this.$unite.getRawType('OPERATOR').enumValues.filter((value) => {
                    return operators.indexOf(value.name) >= 0;
                }).map((value) => {
                    return {
                        id: value.name,
                        label: value.description || value.name,
                    }
                });
            },
            onFieldChange() {
                let operators = this.selectedField.operators;
                this.$set(this.filter, 'operator', operators.length === 1 ? operators[0] : null);
                this.$set(this.filter, 'value', null);
                this.$set(this.filter, 'cast', null);
            }
        }
    }
</script>
