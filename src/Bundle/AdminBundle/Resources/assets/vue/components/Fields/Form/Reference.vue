<template>
  <form-row :domID="domID" :field="field" :alerts="!referencedView ? [{ type: 'warning', message: $t('field.reference.missing_view_warning') }] : violations">
      <div class="uk-input-group uk-flex uk-flex-middle uk-flex-wrap">
        <div class="uk-label uk-label-secondary" v-for="value in values">
          {{ referencedContentTitle(value) }}
          <a href="" @click.prevent="removeValue(value)" class="uk-icon-link"><icon name="x" /></a>
        </div>
        <a :id="domID" @click.prevent="openModal" :disabled="!referencedView" class="uk-icon-button uk-button-light uk-icon-button-small"><icon name="plus" /></a>
      </div>
      <modal v-if="referencedView && selectModalOpen" @hide="selectModalOpen = false">
        <component :is="$unite.getViewType(referencedView)"
                   :view="referencedView"
                   :title="referencedView.name"
                   :highlight-row="highlightRow"
                   :order-by="referencedView.orderBy"
                   :initial-selection="values"
                   :deleted="showDeleted"
                   :embedded="true"
                   :filter="filter"
                   :select="field.list_of ? 'MULTIPLE' : 'SINGLE'"
                   @toggleDeleted="showDeleted = !showDeleted"
                   @select="onSelect"
                   @onCreate="onCreate" />
      </modal>
  </form-row>
</template>
<script>
  import UIkit from 'uikit';
  import _abstract from "./_abstract";
  import FormRow from './_formRow';
  import Icon from "../../Icon";
  import Modal from "../../Modal";
  import gql from 'graphql-tag';
  import Mustache from 'mustache';
  import {getAdminViewByType} from "../../../plugins/unite";

  export default {

      // Static query methods for unite system.
      queryData(field, unite, depth) { return `${ field.id } { id }` },
      normalizeQueryData(inputData, field) {

          if(!inputData || inputData.length === 0) {
              return null;
          }

          if(field.list_of) {
              return inputData.map(row => row.id);
          } else {
              return inputData.id;
          }
      },
      normalizeMutationData(formData, field, unite) { return formData; },

      // Vue properties for this component.
      extends: _abstract,
      components: { FormRow, Icon, Modal },
      data(){
          return {
              filter: null,
              referencedContent: [],
              selectModalOpen: false,
              showDeleted: false,
              highlightRow: null,
          };
      },
      computed: {
          referencedView() {
              let view = this.field.config.formView ? this.$unite.adminViews[this.field.config.formView] : getAdminViewByType(this.$unite, this.field.returnType);
              return view && view.type === this.field.returnType ? view : null;
          },
      },

      watch: {
          selectModalOpen(val) {
              if(val) {
                  this.highlightRow = null;
              }
          }
      },

      apollo: {
          referencedContent: {
              fetchPolicy: 'network-only',
              query() {
                  return gql`
                    ${ this.referencedView.fragment }
                    query{
                      find${ this.referencedView.type } {
                        result {
                          _meta {
                            id
                          }
                          ... ${ this.referencedView.id }
                        }
                      }
                  }`
              },
              update(data) {
                  return data[`find${ this.referencedView.type }`].result;
              }
          }
      },
      methods: {
          onSelect(values) {
              this.val = values;
              this.selectModalOpen = false;
          },
          onCreate(id) {
              this.highlightRow = id;
          },
          referencedContentTitle(id) {
              let refContent = this.referencedContent.filter((content) => { return content._meta.id === id });
              return this.referencedView.contentTitle(refContent.length > 0 ? refContent[0] : { _meta: { id: id } });
          },
          replaceDynamicFilterVars(filter) {

              if(!filter) {
                  return filter;
              }

              let tFilter = Object.assign({}, filter);

              if(filter.value) {
                  tFilter.value = filter.value.map((value) => {
                      return Mustache.render(value, {
                          content: this.rootFormData,
                          row: this.formData,
                          require: function() { return function(text, render){
                              let val = render(text);
                              if(val.length === 0) {
                                  throw text;
                              }
                              return val;
                          }},
                      });
                  });
              }
              if(filter.AND) {
                  tFilter.AND = filter.AND.map(this.replaceDynamicFilterVars);
              }
              if(filter.OR) {
                  tFilter.OR = filter.OR.map(this.replaceDynamicFilterVars);
              }
              return tFilter;
          },
          openModal() {
              try {
                  this.filter = this.replaceDynamicFilterVars(this.referencedView.filter);
                  this.selectModalOpen = true
              } catch (e) {
                  UIkit.modal.alert(this.$t('field.reference.missing_required_value'));
              }
          }
      }
  }
</script>
