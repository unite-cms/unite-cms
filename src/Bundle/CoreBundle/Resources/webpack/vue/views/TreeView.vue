<template>
    <article>
        <view-header :config="config" />
        <view-alerts v-if="alerts.length > 0" :alerts="alerts" />
        <div class="unite-card-table uk-overflow-auto">
            <draggable draggable="tbody" :componentData="{ attrs: { class: 'uk-table uk-table-divider uk-table-striped uk-table-hover uk-table-small uk-table-middle' + (dragging ? 'dragging' : '') } }" :disabled="!canDrag" tag="table" handle=".uk-sortable-handle" v-model="rows" @start="dragging = true" @end="dragging = false" @change="onDraggableChange">
                <tbody v-for="row in rows" :key="row.id">
                    <tr>
                        <td v-for="field in visibleTreeFields" :key="field.identifier" :class="{ 'uk-table-shrink' : getRowFieldComponent(field).FIELD_WIDTH_COLLAPSED, 'uk-table-expand' : getRowFieldComponent(field).FIELD_WIDTH_EXPANDED }">
                            <component v-if="field.type === '_toggle_children'" :is="getRowFieldComponent(field)" :config="config" :field="field" :row="row" @toggle="onChildrenToggle" :open="openState[row.id] || false" />
                            <component v-else :is="getRowFieldComponent(field)" :config="config" :field="field" :row="row" />
                        </td>
                    </tr>
                    <tr v-if="openState[row.id]">
                        <td :colspan="visibleTreeFields.length">TODO: CHILDREN</td>
                    </tr>
                </tbody>
                <thead slot="header">
                    <tr>
                        <th v-for="field in visibleTreeFields" :key="field.identifier">
                            <component v-if="field.type === '_toggle_children'" :is="getHeaderFieldComponent(field)" :config="config" :field="field" @toggle="onHeaderToggle" :open="allOpenState()" />
                            <component v-else :is="getHeaderFieldComponent(field)" :config="config" :field="field" />
                        </th>
                    </tr>
                </thead>
            </draggable>
        </div>
        <view-footer :config="config" />
        <div v-if="config.loading" class="loading uk-text-center"><div uk-spinner></div></div>
    </article>
</template>

<script>

    import AbstractView from './Base/AbstractView';
    import draggable from 'vuedraggable';

    import TreeChildrenToggle from './Base/TreeChildrenToggle';

    export default {
        data() {
            return {
                dragging: false,
                openState: {},
            }
        },
        extends: AbstractView,
        components: { draggable },
        computed: {
            visibleTreeFields() {
                let fields = Object.assign([], this.visibleFields);
                fields.unshift({
                    identifier: '_toggle_children',
                    type: '_toggle_children',
                    virtual: true,
                });
                return fields;
            },
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

            alterRowFieldComponent(field) {
                return field.type === '_toggle_children' ? TreeChildrenToggle : null;
            },

            alterHeaderFieldComponent(field) {
                return field.type === '_toggle_children' ? TreeChildrenToggle : null;
            },

            onDraggableChange(event) {
                this.updateSort(event.moved.element, event.moved.newIndex);
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
            }
        }
    }
</script>
