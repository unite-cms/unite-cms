<template>
  <form-row :domID="domID" :field="field" :alerts="!referencedView ? [{ level: 'warning', message: $t('field.reference.missing_view_warning') }] : []">
      <div class="uk-input-group uk-flex uk-flex-middle uk-flex-wrap">
        <div class="uk-label uk-label-primary" v-for="value in values">
          {{ referencedContentTitle(value) }}
          <a href="" @click.prevent="removeValue(value)" class="uk-icon-link"><icon name="x" /></a>
        </div>
        <a :id="domID" @click.prevent="selectModalOpen = true" :disabled="!referencedView" class="uk-icon-button uk-button-light uk-icon-button-small"><icon name="plus" /></a>
      </div>
      <modal v-if="referencedView && selectModalOpen" @hide="selectModalOpen = false" :title="$t('field.reference.modal.headline')">
        <component :is="$unite.getViewType(referencedView.viewType)" :view="referencedView" :initial-selection="values" :header="false" :select="field.list_of ? 'MULTIPLE' : 'SINGLE'" @select="onSelect" />
      </modal>
  </form-row>
</template>
<script>
  import _abstract from "./_abstract";
  import FormRow from './_formRow';
  import Icon from "../../Icon";
  import Modal from "../../Modal";
  import gql from 'graphql-tag';
  import { getAdminViewByType } from "../../../plugins/unite";

  export default {

      // Static query methods for unite system.
      queryData(field) { return `${ field.id } { id }` },
      normalizeData(inputData, field) {

          if(!inputData || inputData.length === 0) {
              return null;
          }

          if(field.list_of) {
              return inputData.map(row => row.id);
          } else {
              return inputData.id;
          }
      },

      // Vue properties for this component.
      extends: _abstract,
      components: { FormRow, Icon, Modal },
      data(){
          return {
              referencedContent: [],
              selectModalOpen: false,
          };
      },
      computed: {
          referencedView() {

              // TODO: Allow to configure the adminView to use.
              return getAdminViewByType(this.$unite, this.field.returnType);
          }
      },
      apollo: {
          referencedContent: {
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
          referencedContentTitle(id) {
              let refContent = this.referencedContent.filter((content) => { return content._meta.id === id });
              return this.referencedView.contentTitle(refContent.length > 0 ? refContent[0] : { _meta: { id: id } });
          }
      }
  }
</script>
<style scoped lang="scss">
  .uk-label {
    .uk-icon-link {
      margin-right: -5px;
      color: white;
    }
  }
</style>