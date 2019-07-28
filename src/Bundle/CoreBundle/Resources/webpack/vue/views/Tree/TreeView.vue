<template>
    <article>
        <view-header :config="config" />
        <view-alerts v-if="alerts.length > 0" :alerts="alerts" />        
        <tree-rows
            :config="config"
            :showHeader="true"
            :headerFieldComponents="headerFieldComponents" 
            :rowFieldComponents="rowFieldComponents" 
            :canDrag="canDrag"
            @updateSort="onUpdateSort" />
    </article>
</template>

<script>

    import AbstractView from '../Base/AbstractView';

    import TreeChildrenToggle from './TreeChildrenToggle';
    import TreeRows from './TreeRows'

    export default {
        extends: AbstractView,
        components: { TreeRows },
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
            headerFieldComponents() {
                return this.visibleTreeFields.map((field) => {
                    return {
                        type: field.type,
                        component: this.getHeaderFieldComponent(field),
                        props: {
                            config: this.config,
                            field: field,
                            open: false,
                        }
                    }
                });
            },
            rowFieldComponents() {
                return this.visibleTreeFields.map((field) => {
                    let component = this.getRowFieldComponent(field);
                    return {
                        type: field.type,
                        component: component,
                        class: {
                            'uk-table-shrink' : component.FIELD_WIDTH_COLLAPSED, 
                            'uk-table-expand' : component.FIELD_WIDTH_EXPANDED
                        },
                        props: {
                            config: this.config,
                            field: field,
                            open: false,
                        }
                    }
                });
            },
        },
        methods: {

            // Loading is done by TreeRows.vue
            load() {},

            alterRowFieldComponent(field) {
                return field.type === '_toggle_children' ? TreeChildrenToggle : null;
            },

            alterHeaderFieldComponent(field) {
                return field.type === '_toggle_children' ? TreeChildrenToggle : null;
            },

            onUpdateSort(event) {
                this.updateSort(event.moved.element, event.moved.newIndex);
            },
        }
    }
</script>
