<template>
    <article>
        <view-header :config="config" />
        <view-alerts v-if="alerts.length > 0" :alerts="alerts" />
        <div class="unite-card-table uk-overflow-auto">
            <table class="uk-table uk-table-divider uk-table-striped uk-table-hover uk-table-small uk-table-middle" :class="{ 'dragging': dragging }">
                <thead>
                    <tr>
                        <th v-for="field in visibleFields" :key="field.identifier">
                            <component :is="getHeaderFieldComponent(field)" :config="config" :field="field" />
                        </th>
                    </tr>
                </thead>
                <draggable :disabled="!canDrag" tag="tbody" handle=".uk-sortable-handle" v-model="rows" @start="dragging = true" @end="dragging = false" @change="onDraggableChange">
                    <tr v-for="row in rows" :key="row.id">
                        <td v-for="field in visibleFields" :key="field.identifier" :class="{ 'uk-table-shrink' : getRowFieldComponent(field).FIELD_WIDTH_COLLAPSED, 'uk-table-expand' : getRowFieldComponent(field).FIELD_WIDTH_EXPANDED }">
                            <component :is="getRowFieldComponent(field)" :config="config" :field="field" :row="row" />
                        </td>
                    </tr>
                </draggable>
            </table>
        </div>
        <view-footer :config="config" />
        <div v-if="config.loading" class="loading uk-text-center"><div uk-spinner></div></div>
    </article>
</template>

<script>

    import AbstractView from './Base/AbstractView';
    import draggable from 'vuedraggable';

    export default {
        data() {
            return {
                dragging: false,
            }
        },
        extends: AbstractView,
        components: { draggable },
        methods: {
            onDraggableChange(event) {
                this.updateSort(event.moved.element, event.moved.newIndex);
            }
        }
    }
</script>
