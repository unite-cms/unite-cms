<template>
    <div class="uk-overflow-auto table-overflow-container">
        <table class="uk-table uk-table-small uk-table-divider uk-table-middle">
            <thead>
            <tr>
                <th v-if="select"></th>
                <th v-for="field in fields">{{ field.name }}</th>
                <th v-if="!select"></th>
            </tr>
            </thead>
            <tbody class="uk-table-striped">
            <tr v-for="row in rows.result" :class="{ updated: highlightRow === row._meta.id }" :key="row._meta.id" :id="'row-' + row._meta.id">
                <td v-if="select" class="uk-table-shrink">
                    <button @click.prevent="$emit('selectRow', row._meta.id)" class="uk-icon-button uk-icon-button-small" :class="isSelected(row._meta.id) ? 'uk-button-primary' : 'uk-button-default'" uk-icon="check" :title="$t('content.list.selection.select')">
                        <icon v-if="isSelected(row._meta.id)" name="check" />
                    </button>
                </td>
                <td v-for="field in fields">
                    <component :is="$unite.getListFieldType(field)" :view="view" :row="row" :field="field" :embedded="embedded" />
                </td>
                <td v-if="!select" class="uk-table-shrink"><actions-field :view="view" :row="row" id="_actions" :embedded="embedded" /></td>
            </tr>
            </tbody>
            <tfoot v-if="pagination && rows.result.length < rows.total">
            <tr>
                <td :colspan="view.listFields().length + 1">
                    <view-pagination :count="rows.result.length" :total="rows.total" :offset="offset" :limit="view.limit" :mini-pager="view.miniPager || false" @change="page => $emit('updateOffset', page)" />
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
</template>

<script>

    import ViewPagination from "./_pagination";
    import actionsField from '../Fields/List/_actions';
    import Icon from "../Icon";

    export default {
        components: { Icon, ViewPagination, actionsField },
        props: {
            fields: Array,
            rows: Object,
            view: Object,
            offset: Number,
            highlightRow: String,
            embedded: Boolean,
            select: String,
            pagination: Boolean,
            selection: Array,
        },
        methods: {
            isSelected(id) {
                return this.selection.indexOf(id) >= 0;
            },
        }
    }
</script>
