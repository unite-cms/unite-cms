<template>
    <article class="unite-view">
        <view-header :config="config" @search="search" />
        <view-alerts v-if="alerts.length > 0" :alerts="alerts" />  
        <div class="unite-card-table uk-overflow-auto">      
            <tree-rows
                :config="treeConfig"
                :loadChildren="true"
                :parent="null"
                :level="0"
                :headerFieldComponents="headerFieldComponents" 
                :rowFieldComponents="rowFieldComponents" 
                :canDrag="canDrag"
                :searchQuery="searchQuery"
                ref="treeRows"
                @updateSort="onUpdateSort"
                @updateParent="onUpdateParent" />
        </div>
        <div v-if="config.loading" class="loading uk-text-center"><div uk-spinner></div></div>
    </article>
</template>

<script>

    import AbstractView from '../Base/AbstractView';
    import TreeRows from './TreeRows'

    export default {
        extends: AbstractView,
        components: { TreeRows },
        computed: {

            treeConfig() {
                let childenField = ` ${this.config.settings.children_field} { total } `;
                if(this.config.fetcher._fieldsQueryFields.indexOf(childenField) < 0) {
                    this.config.fetcher._fieldsQueryFields.push(childenField);
                }
                return this.config;
            },

            headerFieldComponents() {
                return this.visibleFields.map((field) => {
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
                return this.visibleFields.map((field) => {
                    let component = this.getRowFieldComponent(field);
                    return {
                        type: field.type,
                        component: component,
                        class: {
                            'uk-table-shrink' : component.FIELD_WIDTH_COLLAPSED, 
                            'uk-table-expand' : component.FIELD_WIDTH_EXPANDED,
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
        watch: {
            'config.sort': {
                deep: true,
                handler: function() {
                    this.$refs.treeRows.$emit('reload')
                }
            },
            'config.showOnlyDeletedContent': {
                deep: true,
                handler: function() {
                    this.$refs.treeRows.$emit('reload')
                }
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

            onUpdateParent(event) {
                if(this.canDrag) {
                    let parentData = {};
                    parentData[this.config.settings.parent_field] = {
                        content_type: this.config.content_type,
                        domain: this.config.domain,
                        content: event.parents.pop(),
                    };
                    parentData[this.config.sort.field] = event.added.newIndex;
                    this.update(event.added.element, parentData).then((result) => {
                        if(event.callbacks) {
                            event.callbacks.forEach((cb) => {
                                cb(result);
                            });
                        }
                    });
                }
            }
        }
    }
</script>
