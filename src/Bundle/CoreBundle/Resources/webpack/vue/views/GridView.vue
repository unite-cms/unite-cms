<template>
    <article class="unite-view">
        <view-header :config="config" @search="search" />
        <view-alerts v-if="alerts.length > 0" :alerts="alerts" />
        <draggable :componentData="{ attrs: { class: 'unite-grid-view uk-grid-medium uk-grid-match uk-flex-center uk-grid' } }" :disabled="!canDrag" tag="div" handle=".uk-sortable-handle" v-model="rows" @start="dragging = true" @end="dragging = false" @change="onDraggableChange">
            <div v-for="row in rows" :key="row.id" class="unite-grid-view-item uk-width-1-3@s uk-width-1-5@l uk-flex uk-flex-column">
                <div class="uk-card uk-card-hover uk-card-default">
                    <div class="unite-grid-view-fields uk-flex uk-flex-column uk-flex-center">
                        <div class="unite-grid-view-field" v-for="field in visibleMainFields" :key="field.identifier" :class="'field-type-' + field.type">
                            <component :is="getRowFieldComponent(field)" :config="config" :field="field" :row="row" />
                        </div>
                    </div>
                </div>
                <div class="unite-grid-view-meta-fields">
                    <div class="unite-grid-view-field" v-for="field in visibleMetaFields" :key="field.identifier" :class="'field-type-' + field.type">
                        <component :is="getRowFieldComponent(field)" :config="config" :field="field" :row="row" />
                    </div>
                </div>
            </div>
        </draggable>
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
        computed: {
            visibleMainFields() {
                return this.visibleFields.filter((field) => !this.isMetaField(field));
            },
            visibleMetaFields() {
                return this.visibleFields.filter((field) => this.isMetaField(field));
            },
        },
        methods: {

            isMetaField(field) {
                return field.meta;
            },

            onDraggableChange(event) {
                this.updateSort(event.moved.element, event.moved.newIndex);
            }
        }
    }
</script>
