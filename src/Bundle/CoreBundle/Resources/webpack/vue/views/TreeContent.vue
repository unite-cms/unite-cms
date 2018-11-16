<template>
    <div class="unite-div-table unite-div-tree-view">
        <div class="unite-div-table-thead">
            <table-content-header-row :fields="fields"
                                      :is-sortable="isSortable"
                                      :is-updateable="updateable"
                                      :show-actions="showActions"
                                      :sort-config="sortConfig"
                                      @updateSort="updateSort"
                                      :urls="urls"
                                      :embedded="embedded"
            ></table-content-header-row>
        </div>
        <tree-view-children :isSortable="isSortable"
                            :showActions="showActions"
                            :updateable="updateable"
                            :rows="rows"
                            :fields="fields"
                            :children-field="settings.children_field"
                            :parent-field="settings.parent_field"
                            :urls="urls"
                            :embedded="embedded"
                            @moved="moved"
        ></tree-view-children>
    </div>
</template>

<script>

    import TableContent from './TableContent.vue';
    import TreeViewChildren from '../views/Fields/TreeViewChildren.vue';

    export default {
        extends: TableContent,
        methods: {
            renderField(field, identifier) {
                return TableContent.methods.renderField.call(this) && (!identifier === this.settings.children_field);
            },
        },
        components: {
            'tree-view-children': TreeViewChildren
        }
    }
</script>

<style scoped lang="scss">
    .unite-div-tree-view {
        padding: 0 20px 20px;
        margin-top: -40px;
        border-top: none;

        .unite-div-table-thead {
            background: none;
            padding-top: 20px;

            .unite-div-table-row {
                background: none;
                border-bottom: none;
            }
        }
    }
</style>
