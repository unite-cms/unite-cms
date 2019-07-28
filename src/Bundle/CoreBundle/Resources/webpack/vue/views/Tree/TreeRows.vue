<template>
    <div class="unite-card-table uk-overflow-auto">
        <draggable draggable="tbody" :componentData="{ attrs: { class: 'uk-table uk-table-divider uk-table-striped uk-table-hover uk-table-small uk-table-middle' + (dragging ? 'dragging' : '') } }" :disabled="!canDrag" tag="table" handle=".uk-sortable-handle" v-model="rows" @start="dragging = true" @end="dragging = false" @change="onDraggableChange">
            <tbody v-for="row in rows" :key="row.id">
                <tr>
                    <td v-for="field in rowFieldComponents" :key="field.identifier" :class="field.class">
                        <component v-if="field.type !== '_toggle_children'" :is="field.component" v-bind="field.props" :row="row" />
                        <component v-else :is="field.component" v-bind="field.props" :row="row" @toggle="onChildrenToggle" :hasChildren="!openState[row.id] || createChildrenConfig(row).total > 0" :open="openState[row.id]" />
                    </td>
                </tr>
                <tr v-if="openState[row.id]">
                    <td class="table-holder" :colspan="rowFieldComponents.length">
                        <tree-rows
                            :config="createChildrenConfig(row)"
                            :showHeader="false"
                            :rowFieldComponents="rowFieldComponents" 
                            :canDrag="canDrag"
                            @updateSort="onDraggableChange" />
                    </td>
                </tr>
            </tbody>
            <thead slot="header" v-if="showHeader">
                <tr>
                    <th v-for="field in headerFieldComponents" :key="field.identifier" :class="field.class">
                        <component v-if="field.type !== '_toggle_children'" :is="field.component" v-bind="field.props" />
                        <component v-else :is="field.component" v-bind="field.props" @toggle="onHeaderToggle" :open="allOpenState()" />
                    </th>
                </tr>
            </thead>
            <tfoot v-if="rows.length > 0 && config.total > rows.length">
                <button class="uk-button uk-button-default uk-button-small" @click.prevent="loadMore">Load more</button>
            </tfoot>
        </draggable>
        <div v-if="config.loading" class="loading uk-text-center"><div uk-spinner></div></div>
    </div>
</template>

<script>

    import feather from 'feather-icons';
    import draggable from 'vuedraggable';
    import cloneDeep from 'lodash/cloneDeep';

    export default {
        name: 'treeRows',
        components: { draggable },
        props: {
            config: Object,
            showHeader: Boolean,
            canDrag: Boolean,
            initialRows: Array,
            headerFieldComponents: Array,
            rowFieldComponents: Array,
            config: Object,
        },
        data() {
            return {
                rows: [],
                childrenConfig: {},
                dragging: false,
                openState: {},
            }
        },
        mounted() {
            this.config
                .loadRows()
                .then(rows => this.rows = Object.assign([], rows));
        },
        methods: {
            allOpenState() {
                let haveOpen = false;
                let haveClose = false;
                Object.keys(this.openState).forEach((key) => {
                    if(this.openState[key] === true) {
                        haveOpen = true;
                    }
                    if(this.openState[key] === false) {
                        haveClose = true;
                    }
                });

                if(haveOpen && haveClose) {
                    return true;
                }

                else if(haveOpen) {
                    return true;
                }

                else {
                    return false;
                }
            },

            onDraggableChange(event) {
                this.$emit('updateSort', event);
            },

            onHeaderToggle() {
                let state = this.allOpenState();
                this.rows.forEach((row) => {
                    this.openState[row.id] = !state;
                });
                this.$forceUpdate();
            },

            onChildrenToggle(row) {
                this.openState[row.id] = this.openState[row.id] ? !this.openState[row.id] : true;
                this.$forceUpdate();
            },

            loadMore() {
                this.config.page++;
                this.config.loadRows().then(rows => {
                    this.rows = [...this.rows, ...rows];
                });
            },

            createChildrenConfig(row) {
                if(!this.childrenConfig[row.id]) {
                    let config = cloneDeep(this.config);
                    config._staticFilter = {
                        field: `${config.settings.parent_field}.content`,
                        operator: '=',
                        value: row.id,
                    };
                    this.childrenConfig[row.id] = config;
                }
                return this.childrenConfig[row.id];
            },
        }
    }
</script>
<style lang="scss" scoped>
    td.table-holder {
        padding: 0 !important;

        .unite-card-table {
            margin: 0 0 0 50px;
            border: none;
        }
    }
</style>