<template>
    <div class="uk-width-expand uk-placeholder uk-padding-small">

        <ul class="uk-subnav uk-subnav-divider" uk-margin>
            <li :class="{'uk-active': filter.field !== undefined}"><a href="#" @click.prevent="filter = { field: null, operator: null }">{{ $t('content.list.filter.filter') }}</a></li>
            <li :class="{'uk-active': filter.AND}"><a href="#" @click.prevent="filter = { AND: [] }">{{ $t('content.list.filter.AND') }}</a></li>
            <li :class="{'uk-active': filter.OR}"><a href="#" @click.prevent="filter = { OR: [] }">{{ $t('content.list.filter.OR') }}</a></li>
        </ul>

        <div v-if="filter.AND">
            <div v-for="(subFilter, delta) in filter.AND" class="uk-flex uk-flex-middle">
                <filter-rule :rules="rules" :key="'AND_' + delta" v-model="filter.AND[delta]" />
                <button type="button" class="uk-icon uk-text-danger uk-margin-small-left" @click.prevent="deleteFilter(filter.AND, delta)"><icon name="minus-circle" /></button>
            </div>
            <a class="uk-icon-button uk-button-light uk-icon-button-small" @click.prevent="addFilter(filter.AND)"><icon name="plus" /></a>
        </div>
        <div v-else-if="filter.OR">
            <div v-for="(subFilter, delta) in filter.OR" class="uk-flex uk-flex-middle">
                <filter-rule :rules="rules" :key="'AND_' + delta" v-model="filter.OR[delta]" />
                <button type="button" class="uk-icon uk-text-danger uk-margin-small-left" @click.prevent="deleteFilter(filter.OR, delta)"><icon name="minus-circle" /></button>
            </div>
            <a class="uk-icon-button uk-button-light uk-icon-button-small" @click.prevent="addFilter(filter.OR)"><icon name="plus" /></a>
        </div>
        <div v-else>
            <div class="uk-grid-small" uk-grid>
                <div class="uk-width-1-6">
                    <select class="uk-select" v-model="selectedRule" @change="onFieldChange" required>
                        <option :value="null" disabled>{{ $t('content.list.filter.field') }}</option>
                        <option v-for="(rule, delta) in rules" :value="delta">{{ rule.label }}</option>
                    </select>
                </div>
                <div class="uk-width-1-6" v-if="selectedRule !== null">
                    <select class="uk-select" v-model="filter.operator" required>
                        <option :value="null" disabled>{{ $t('content.list.filter.operator') }}</option>
                        <option v-for="operator in operators(rules[selectedRule].operators)" :value="operator.id">{{ operator.label }}</option>
                    </select>
                </div>
                <div class="uk-width-2-3">
                    <component v-if="selectedRule !== null" :is="rules[selectedRule].input" v-bind="rules[selectedRule].inputProps" v-model="filter.value" />
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

            let filter = this.value && Object.keys(this.value).length > 0 ? this.value : { field: null, operator: null };
            let selectedRule = null;

            if(filter) {
                let rule = this.rules.filter((rule) => {
                    return rule.id === filter.field &&
                        ((!rule.path && !filter.path) || rule.path === filter.path);
                });

                if(rule.length > 0) {
                    selectedRule = this.rules.indexOf(rule[0]);
                }
            }

            return {
                selectedRule,
                filter,
            };
        },
        watch: {
            filter: {
                deep: true,
                handler(filter) {
                    this.$emit('input', filter);
                }
            }
        },
        props: {
            value: Object,
            rules: Array,
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
                let operators = this.rules[this.selectedRule].operators;
                this.$set(this.filter, 'field', this.rules[this.selectedRule].id);
                this.$set(this.filter, 'path', this.rules[this.selectedRule].path);
                this.$set(this.filter, 'operator', operators[0]);
                this.$set(this.filter, 'value', null);
                this.$set(this.filter, 'cast', null);
            }
        }
    }
</script>
