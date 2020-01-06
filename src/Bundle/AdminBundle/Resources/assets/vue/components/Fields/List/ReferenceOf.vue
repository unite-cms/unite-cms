<template>
  <div>
    <div class="uk-flex uk-flex-middle">
      <span v-if="total !== null" class="uk-margin-small-right uk-label uk-label-muted">{{ total }}</span>
      <button type="button" class="uk-button-light uk-icon-button uk-icon-button-small" @click.prevent="modalIsOpen = true"><icon name="menu" /></button>
    </div>
    <div v-if="!referencedView" class="uk-alert-warning" uk-alert>{{ $t('field.reference_of.missing_view_warning') }}</div>
    <modal v-if="referencedView && modalIsOpen" @hide="modalIsOpen = false">
      <component :is="$unite.getViewType(referencedView)"
                 :view="referencedView"
                 :title="$t('field.reference_of.modal.headline', { name: field.name, contentTitle: contentTitle })"
                 :embedded="true"
                 :deleted="showDeleted"
                 :highlight-row="highlightRow"
                 :filter="filter"
                 :order-by="referencedView.orderBy"
                 :initial-create-data="initialCreateData"
                 @onCreate="onCreate"
                 @toggleDeleted="showDeleted = !showDeleted" />
    </modal>
  </div>
</template>
<script>
    import _abstract from "./_abstract";
    import Icon from "../../Icon";
    import Modal from "../../Modal";
    import { getAdminViewByType } from "../../../plugins/unite";

    export default {
        components: {Modal, Icon},
        extends: _abstract,
        data() {
            return {
                highlightRow: null,
                modalIsOpen: false,
                showDeleted: false,
            }
        },

        watch: {
            modalIsOpen(val) {

                // If content was updated, tell this the parent view on modal close.
                if(!val && this.highlightRow && this.$route.query.updated !== this.id) {
                    let query = Object.assign({}, this.$route.query);
                    query.updated = this.id;

                    this.$router.push({
                        path: this.$route.path,
                        query: query,
                    });
                }

                // On open, reset highlightRow
                else {
                  this.highlightRow = null;
                }
            }
        },

        computed: {
            referencedView() {
                let view = this.field.config.listView ? this.$unite.adminViews[this.field.config.listView] : getAdminViewByType(this.$unite, this.field.config.content_type);
                return view && view.type === this.field.config.content_type ? view : null;
            },
            contentTitle() {
                return this.view.contentTitle(this.row);
            },
            filter() {
                return {
                    field: this.field.config.reference_field,
                    operator: 'EQ',
                    value: this.id
                }
            },
            initialCreateData() {
                let formData = {};
                formData[this.field.config.reference_field] = this.id;
                return formData;
            },
            total() {

                if(!this.row[this.field.id]) {
                    return null;
                }

                return this.row[this.field.id].total;
            }
        },
        methods: {
            onCreate(id) {
                this.highlightRow = id;
            }
        }
    }
</script>
<style scoped lang="scss">
  .uk-alert {
    padding: 5px 10px;
    font-size: 0.8rem;
  }
</style>
