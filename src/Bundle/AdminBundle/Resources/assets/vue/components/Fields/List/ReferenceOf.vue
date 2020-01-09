<template>
  <div>
    <div class="uk-flex uk-flex-middle">
      <span v-if="total !== null" class="uk-margin-small-right uk-label uk-label-muted">{{ total }}</span>
      <button type="button" class="uk-button-light uk-icon-button uk-icon-button-small small-padding" @click.prevent="modalIsOpen = true"><icon name="list" /></button>
      <button v-if="referencedView.permissions.create" type="button" class="uk-button-light uk-icon-button uk-icon-button-small uk-margin-small-left small-padding" @click.prevent="createContent"><icon name="plus" /></button>
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

    export const createContentLink = function(viewGroup, referencedView, referenceFieldId, id){
        let group = viewGroup;
        if(referencedView.groups.length === 0) {
            group = '_all_';
        }

        else if (referencedView.groups.indexOf(group) < 0) {
            group = referencedView.groups[0].name;
        }

        let referencedField = referencedView.fields.filter((field) => {
            return field.id === referenceFieldId;
        });

        if(referencedField.length === 0) {
            return;
        }

        let path = ["", referencedView.category, group, referencedView.id, 'create'].join('/');
        let query = { updated: id };
        query['initial_value_' + referenceFieldId] = JSON.stringify(referencedField[0].list_of ? [id] : id);
        return {path, query};
    };

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

                let referencedField = this.referencedView.fields.filter((field) => {
                    return field.id === this.field.config.reference_field;
                });

                let isListOf = referencedField.length > 0 && referencedField[0].list_of;

                return {
                    field: this.field.config.reference_field,
                    operator: isListOf ? 'CONTAINS' : 'EQ',
                    value: isListOf ? `"${this.id}"` : this.id
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
            },
            createContent() {
                this.$router.push(createContentLink(this.$route.params.viewGroup, this.referencedView, this.field.config.reference_field, this.id));
            }
        }
    }
</script>
<style scoped lang="scss">
  button {
    cursor: pointer;
  }

  .uk-alert {
    padding: 5px 10px;
    font-size: 0.8rem;
  }
</style>
