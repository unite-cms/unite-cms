<template>
    <div>
        <draggable group="tree" draggable="tbody" :componentData="{ attrs: { class: 'uk-table uk-table-divider uk-table-striped uk-table-hover uk-table-small uk-table-middle' + (dragging ? 'dragging' : '') } }" :disabled="!canDrag" tag="table" handle=".uk-sortable-handle" v-model="rows" @start="dragging = true" @end="dragging = false" @change="onDraggableChange">
            <tbody v-for="row in rows" :key="row.id">
                <tr>
                    <td v-for="field in rowFieldComponents" :key="field.props.field.identifier" :class="Object.assign(field.class, { 'uk-table-expand': (field.props.field.identifier === firstNonVirtualField), 'uk-table-shrink': (field.props.field.identifier !== firstNonVirtualField) })">
                        <tree-children-toggle v-if="field.props.field.identifier === firstNonVirtualField && hasChildren(row)" :row="row" @toggle="onChildrenToggle" :open="openState[row.id]" />
                        <component :is="field.component" v-bind="field.props" :row="row" />
                    </td>
                </tr>
                <tr v-if="hasChildren(row) && openState[row.id]" class="table-holder">
                    <td :colspan="rowFieldComponents.length">
                        <tree-rows
                            :config="createChildrenConfig(row)"
                            :parent="row.id"
                            :level="level + 1"
                            :rowFieldComponents="rowFieldComponents" 
                            :canDrag="canDrag"
                            @updateSort="onDraggableChange"
                            @updateParent="onDraggableChange" />
                    </td>
                </tr>
            </tbody>
            <thead slot="header" v-if="level === 0">
                <tr>
                    <th v-for="field in headerFieldComponents" :key="field.props.field.identifier" :class="field.class">
                        <tree-children-toggle v-if="field.props.field.identifier === firstNonVirtualField" @toggle="onHeaderToggle" :open="allOpenState()" />
                        <component :is="field.component" v-bind="field.props" />                    
                    </th>
                </tr>
            </thead>
        </draggable>
        <footer v-if="rows.length > 0 && config.total > rows.length" class="uk-padding-small">
            <button :title="config.t('Load more')" uk-tooltip class="uk-button uk-button-default uk-button-small" @click.prevent="loadMore" v-html="feather.icons['more-horizontal'].toSvg({ width: 16, height: 16 })"></button>
        </footer>
        <div v-if="level > 0 && reactiveConfig.loading" class="loading uk-text-center"><div uk-spinner></div></div>
    </div>
</template>

<script>

    import feather from 'feather-icons';
    import draggable from 'vuedraggable';
    import cloneDeep from 'lodash/cloneDeep';

    import TreeChildrenToggle from './TreeChildrenToggle';

    export default {
        name: 'treeRows',
        components: { draggable, TreeChildrenToggle },
        props: {
            parent: String,
            config: Object,
            level: Number,
            canDrag: Boolean,
            initialRows: Array,
            headerFieldComponents: Array,
            rowFieldComponents: Array,
            config: Object,
        },
        data() {
            return {
                reactiveConfig: this.config,
                rows: [],
                childrenConfig: {},
                dragging: false,
                openState: {},
            }
        },
        mounted() {
            this.$on('reload', () => {
                this.config
                .loadRows()
                .then((rows) => {
                    let oldOpenState = this.openState;
                    this.openState = {};
                    this.rows = Object.assign([], rows);
                    this.$nextTick(() => {
                        this.openState = oldOpenState;
                    });
                });
            });
            this.$emit('reload');
        },
        computed: {
            feather() {
                return feather;
            },
            firstNonVirtualField() {
                let field = null;
                this.rowFieldComponents.forEach((f) => {
                    if(!field && !f.props.field.virtual) {
                        field = f.props.field.identifier;
                    }
                });
                return field;
            }
        },
        methods: {

            hasChildren(row) {
                return row.get(this.config.settings.children_field, { total: 0 }).total > 0;
            },

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

            update(row, data) {
                this.config.updateRow(row, data)
                    .catch((error) => {
                        this.load();
                        this.alert(error, 'danger');
                    });
            },

            onDraggableChange(event) {

                event.parents = event.parents || [];
                event.parents.unshift(this.parent);

                if(event.moved) {
                    this.$emit('updateSort', event);
                }
                if(event.added) {
                    this.$emit('updateParent', event);
                }
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

                // This values should always be updated
                this.childrenConfig[row.id].sort = this.config.sort;

                return this.childrenConfig[row.id];
            },
        }
    }
</script>
<style lang="scss" scoped>
    .uk-table {
        margin-bottom: 0;
    }
    .loading {
        height: 20px;
        width: 20px;
        margin: 15px;
    }
</style>