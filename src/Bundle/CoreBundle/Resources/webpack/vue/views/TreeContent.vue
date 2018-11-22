<template>
    <div class="unite-div-table unite-div-tree-view">
        <tree-view-children :isSortable="isSortable"
                            :showActions="showActions"
                            :updateable="updateable"
                            :rows="rows"
                            :fields="fieldWithoutChildren"
                            :children-field="settings.children_field"
                            :parent-field="settings.parent_field"
                            :content-type="settings.content_type"
                            :domain="settings.domain"
                            :urls="urls"
                            :embedded="embedded"
                            :dataFetcher="dataFetcher"
                            :sort="sort"
                            @moved="moved"
        ></tree-view-children>
    </div>
</template>

<script>

    import TableContent from './TableContent.vue';
    import TreeViewChildren from '../views/Fields/TreeViewChildren.vue';

    export default {
        extends: TableContent,
        computed: {
            fieldWithoutChildren() {
                let realFields = {};
                Object.keys(this.fields).map((field) => {
                    if(field !== this.settings.children_field) {
                        realFields[field] = this.fields[field];
                    }
                });
                return realFields;
            }
        },
        components: {
            'tree-view-children': TreeViewChildren
        }
    }
</script>
