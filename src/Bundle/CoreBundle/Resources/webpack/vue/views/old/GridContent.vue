<template>
    <div class="unite-grid-view uk-grid-medium uk-grid-match uk-flex-center" uk-grid :uk-sortable="isSortable && updateable ? 'handle: .uk-sortable-handle' : null" v-on:moved="moved">
        <div class="unite-grid-view-item uk-width-1-3@s uk-width-1-5@l uk-flex uk-flex-column" :data-id="row.id" :key="row.id" v-for="row in rows">
            <div class="uk-card uk-card-hover uk-card-default">
                <div class="unite-grid-view-fields uk-flex uk-flex-column uk-flex-center">
                    <component v-for="(field,identifier) in fields" v-if="renderField(field, identifier, false)"
                               :key="identifier"
                               :is="$uniteCMSViewFields.resolve(field.type)"
                               :type="field.type"
                               :identifier="identifier"
                               :label="field.label"
                               :settings="field.settings"
                               :sortable="isSortable"
                               initialMinWidth="0"
                               :row="row"
                               :ref="'field_' + identifier"></component>
                </div>
                <base-view-row-actions v-if="showActions"
                                       :row="row"
                                       :urls="urls"
                                       identifier="_actions"
                                       ref="field__actions"
                                       initialMinWidth="0"
                                       :refInFor="true"
                                       :embedded="embedded"
                ></base-view-row-actions>
            </div>
            <div class="unite-grid-view-meta-fields">
                <component v-for="(field,identifier) in fields" v-if="renderField(field, identifier, true)"
                           :key="identifier"
                           :is="$uniteCMSViewFields.resolve(field.type)"
                           :type="field.type"
                           :identifier="identifier"
                           :label="field.label"
                           :settings="field.settings"
                           :sortable="isSortable"
                           initialMinWidth="0"
                           :row="row"
                           :ref="'field_' + identifier"></component>
            </div>
        </div>
    </div>
</template>

<script>

    import UIkit from 'uikit';

    import BaseViewContent from './Base/BaseViewContent.vue';
    import BaseViewContentHeaderField from './Base/BaseViewContentHeaderField.vue';
    import BaseViewRowActions from './Base/BaseViewRowActions.vue';

    export default {
        extends: BaseViewContent,
        data() {
            return {
                sortConfig: this.sort,
            };
        },
        computed: {
            showActions() {
                return !this.selectable;
            },
            isSortable() {
                return !!this.sort.sortable && !this.selectable && this.updateable;
            }
        },
        mounted: function(){
            let findModal = (element) => {
                if(element.hasAttribute('uk-modal')) {
                    return element;
                }
                if(!element.parentElement) {
                    return null;
                }
                return findModal(element.parentElement);
            };
            let modal = findModal(this.$el);
            if(modal) {
                UIkit.util.on(modal, 'show', () => {
                    this.$nextTick(() => {
                        Object.keys(this.fields).forEach((identifier) => {
                            this.$refs['field_' + identifier].forEach((ref) => {
                                ref.calcWidth();
                            });
                        });
                    });
                });
            }
        },
        methods: {
            setSort(identifier) {
                if(!this.isSortable) {
                    this.sortConfig.field = identifier;
                    this.sortConfig.asc = this.sortConfig.field === identifier ? !this.sortConfig.asc : true;
                    this.$emit('updateSort', this.sortConfig);
                }
            },

            moved(event) {
                if(this.isSortable) {
                    this.$emit('updateRow', {
                        id: event.detail[1].dataset.id,
                        data: {
                            position: UIkit.util.index(event.detail[1])
                        }
                    });
                }
            },
            renderField(field, identifier, meta = false) {

                if(!this.isSortable && !this.updateable && this.sortConfig.field === field.identifier) {
                    return false;
                }

                if(!this.updateable && field.type === 'selectrow') {
                    return false;
                }

                return !!field.meta === meta;
            }
        },
        components: {
            'base-view-row-actions': BaseViewRowActions,
            'base-content-header-field': BaseViewContentHeaderField
        }
    }
</script>

<style scoped>

</style>
