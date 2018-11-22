<template>
    <div class="unite-div-table-tbody">
        <div class="unite-div-tree-view-element" :data-id="row.id" :key="row.id" v-for="row in rows">
            <table-content-row class="unite-div-tree-view-row uk-box-shadow-small uk-box-shadow-hover-medium"
                               :row="row"
                               :fields="fields"
                               :is-sortable="isSortable"
                               :is-updateable="updateable"
                               :show-actions="showActions"
                               :sort-config="sortConfig"
                               :urls="urls"
                               :embedded="embedded"
            ></table-content-row>
            <tree-view-children :isSortable="isSortable" v-if="childRows(row).length > 0"
                                :showActions="showActions"
                                :updateable="updateable"
                                :rows="childRows(row)"
                                :fields="fields"
                                :children-field="childrenField"
                                :parent-field="parentField"
                                :urls="urls"
                                :embedded="embedded"
                                :dataFetcher="dataFetcher"
                                :sort="sort"
            ></tree-view-children>
        </div>
    </div>
</template>

<script>

    import UIkit from 'uikit';
    import nestable from 'uikit3-nestable/src/js/nestable';

    UIkit.mixin(nestable, 'sortable');

    import BaseField from '../Base/BaseField.vue';
    import TableContentRow from '../TableContentRow.vue';
    import BaseViewRowActions from '../Base/BaseViewRowActions.vue';

    export default {
        name: 'tree-view-children',
        extends: BaseField,
        data() {
            return {
                sortConfig: this.sort,
            };
        },
        props: ['isSortable', 'showActions', 'updateable', 'rows', 'fields', 'childrenField', 'contentType', 'domain', 'parentField', 'urls', 'embedded', 'dataFetcher', 'sort'],

        mounted() {
            if(this.isSortable && this.updateable) {
                this.$nextTick(() => {
                    setTimeout(() => {
                        UIkit.sortable(this.$el, {
                            handle: '.uk-sortable-handle',
                            nestableContainerClass: 'unite-div-table-tbody',
                            nestable: true
                        });
                        UIkit.util.on(this.$el, 'moved', this.moved);
                        UIkit.util.on(this.$el, 'added', this.nest);
                    }, 100);
                });
            }
        },

        methods: {

            /**
             * @inheritdoc
             */
            fieldQuery(identifier, field, $uniteCMSViewFields) {
                return identifier + '(sort: {field: "' + field.settings.sort.field + '", order: "' + (field.settings.sort.asc ? 'ASC' : 'DESC') + '"}) { result { ' + Object.keys(field.settings.fields).map((identifier) => {
                    return $uniteCMSViewFields.resolveFieldQueryFunction(field.settings.fields[identifier].type)(
                        identifier,
                        field.settings.fields[identifier],
                        $uniteCMSViewFields
                    );
                }).join(', ') + ' } }';
            },

            /**
             * @inheritdoc
             */
            filterQuery(identifier, field) {

                // At the moment, we can't filter by referenced values. We can implement this, once
                // https://github.com/unite-cms/unite-cms/issues/326 is resolved.
               return null;
            },

            childRows(row) {
                return row[this.childrenField].result.map((cRow) => {
                    cRow['_actions'] = row['_actions'];
                    return cRow;
                });
            },

            moved(event) {
                if(this.isSortable) {
                    let data = {};
                    data[this.sort.field] = UIkit.util.index(event.detail[1]);
                    this.updateRow(event.detail[1].dataset.id, data);
                }
            },

            nest(event) {
                if(this.isSortable) {
                    let data = {};
                    if(event.detail[0].$el.parentElement.classList.contains('unite-div-tree-view-element')) {
                        data[this.parentField] = {
                            content: event.detail[0].$el.parentElement.dataset.id,
                            content_type: this.contentType,
                            domain: this.domain
                        };
                    } else {
                        data[this.parentField] = null;
                    }

                    data[this.sort.field] = UIkit.util.index(event.detail[1]);

                    this.updateRow(event.detail[1].dataset.id, data);
                }
            },

            updateRow(id, data) {
                this.dataFetcher.update(id, data).then(
                    (data) => {
                        let rowToUpdate = this.rows.filter((row) => { return row.id === id });
                        if(rowToUpdate.length > 0) {
                            ['updated'].concat(Object.keys(data)).forEach((field) => {
                                rowToUpdate[0][field] = data[field];
                            });
                        }
                    },
                    (error) => { this.error = 'API Error: ' + error; })
                    .catch(() => { this.error = "An error occurred, while trying to fetch data."; })
                    .finally(() => { this.loading = false; });
            }
        },
        components: {
            'base-view-row-actions': BaseViewRowActions,
            'table-content-row': TableContentRow
        }
    }
</script>

<style scoped lang="scss">
    .unite-div-tree-view-element {
        margin-bottom: 10px;

        .view-field-actions {
            padding-top: 0;
            padding-bottom: 0;
            padding-right: 10px;
        }
    }

    .uk-sortable-placeholder {
        position: relative;
        opacity: 1;
    }

    .uk-sortable-placeholder > * {
        opacity: 0;
    }

    .uk-sortable-placeholder:after {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        border: 1px dashed #CCC;
        opacity: 1;
    }
</style>
