<template>
    <div class="unite-div-table">
        <div class="unite-div-table-thead">
            <div :style="rowStyle" class="unite-div-table-row">
                <base-content-header-field v-for="(field,identifier) in fields" v-if="renderField(field, identifier)"
                                           :key="identifier"
                                           :identifier="identifier"
                                           :label="field.label"
                                           :type="field.type"
                                           :isSortable="isSortable"
                                           :initialMinWidth="minWidthMap[identifier]"
                                           :sort="sortConfig"
                                           :fixedWidth="hasColumnFixedWidth(identifier)"
                                           @sortChanged="setSort"
                                           @resized="onFieldResize"
                                           :ref="'field_' + identifier"></base-content-header-field>
                <base-content-header-field v-if="showActions" v-for="i in [1]"
                                           :key="i"
                                           identifier="_actions"
                                           type="_actions"
                                           :sort="sortConfig"
                                           :initialMinWidth="minWidthMap['_actions']"
                                           label=""
                                           :fixedWidth="true"
                                           @resized="onFieldResize"
                                           ref="field__actions"
                ></base-content-header-field>
            </div>
        </div>
        <div class="unite-div-table-tbody" :uk-sortable="isSortable && updateable ? 'handle: .uk-sortable-handle' : null" v-on:moved="moved">
            <div uk-grid :style="rowStyle" class="unite-div-table-row" :data-id="row.id" :key="row.id" v-for="row in rows">
                <component v-for="(field,identifier) in fields" v-if="renderField(field, identifier)"
                           :key="identifier"
                           :is="$uniteCMSViewFields.resolve(field.type)"
                           :type="field.type"
                           :identifier="identifier"
                           :label="field.label"
                           :settings="field.settings"
                           :initialMinWidth="minWidthMap[identifier]"
                           :sortable="isSortable"
                           :row="row"
                           @resized="onFieldResize"
                           :ref="'field_' + identifier"></component>
                <base-view-row-actions v-if="showActions"
                                       :row="row"
                                       :urls="urls"
                                       identifier="_actions"
                                       :initialMinWidth="minWidthMap['_actions']"
                                       @resized="onFieldResize"
                                       ref="field__actions"
                                       :refInFor="true"
                ></base-view-row-actions>
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
                minWidthMap: {},
                rowMinWidth: 0,
                sortConfig: this.sort,
            };
        },
        computed: {
            showActions() {
                return !this.selectable;
            },
            isSortable() {
                return !!this.sort.sortable && !this.selectable && this.updateable;
            },
            rowStyle() {
                return this.rowMinWidth > 0 ? { 'min-width': this.rowMinWidth + 'px' } : {};
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

            // When a child now its size or the size changes, onFieldResize will be called.
            onFieldResize(event) {
                if(!this.minWidthMap[event.identifier] || this.minWidthMap[event.identifier] < event.width) {
                    this.minWidthMap[event.identifier] = event.width;

                    // Dispatch the new min width to all fields.
                    if(typeof this.$refs['field_' + event.identifier] !== 'undefined') {
                        if (typeof this.$refs['field_' + event.identifier].forEach === 'undefined') {
                            this.$refs['field_' + event.identifier].$emit('minWidthChanged', event.width);
                        }
                        else {
                            this.$refs['field_' + event.identifier].forEach((ref) => {
                                ref.$emit('minWidthChanged', event.width);
                            });
                        }
                    }

                    // Recalc row min width.
                    this.$nextTick(() => {
                        this.rowMinWidth = this.$el.querySelector('.unite-div-table-row').scrollWidth;
                    });
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
            hasColumnFixedWidth(identifier) {
                if(this.$refs['field_' + identifier] && this.$refs['field_' + identifier].length > 1 && this.$refs['field_' + identifier][1].$el.nodeType === Node.ELEMENT_NODE) {
                    return this.$refs['field_' + identifier][1].$el.classList.contains('fixed-width');
                }
                return false;
            },
            renderField(field, identifier) {

                if(!this.isSortable && !this.updateable && this.sortConfig.field === field.identifier) {
                    return false;
                }

                if(!this.updateable && field.type === 'selectrow') {
                    return false;
                }

                return true;
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
