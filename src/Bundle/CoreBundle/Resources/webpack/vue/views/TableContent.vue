<template>
    <div class="unite-card-table">
        <div class="unite-div-table">
            <div class="unite-div-table-thead">
                <table-content-header-row :fields="fields"
                                          :is-sortable="isSortable"
                                          :is-updateable="updateable"
                                          :show-actions="showActions"
                                          :sort-config="sortConfig"
                                          @updateSort="updateSort"
                                          :urls="urls"
                                          :embedded="embedded"
                ></table-content-header-row>
            </div>
            <div class="unite-div-table-tbody" :uk-sortable="isSortable && updateable ? 'handle: .uk-sortable-handle' : null" v-on:moved="moved">
                <table-content-row v-for="row in rows"
                                   :row="row"
                                   :fields="fields"
                                   :is-sortable="isSortable"
                                   :is-updateable="updateable"
                                   :show-actions="showActions"
                                   :sort-config="sortConfig"
                                   :urls="urls"
                                   :embedded="embedded"
                ></table-content-row>
            </div>
        </div>
    </div>
</template>

<script>

    import UIkit from 'uikit';
    import BaseViewContent from './Base/BaseViewContent.vue';
    import TableContentRow from './TableContentRow.vue';
    import TableContentHeaderRow from './TableContentHeaderRow.vue';

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

            updateSort(event) { this.emit('updateSort', event); },

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
            }
        },
        components: {
            'table-content-row': TableContentRow,
            'table-content-header-row': TableContentHeaderRow,
        }
    }
</script>

<style scoped>

</style>
