<template>
  <div class="uk-margin">
    <label class="uk-form-label" :for="domID">{{ name }}</label>
    <div class="uk-form-controls">
      <div class="uk-input-group uk-flex uk-flex-middle uk-flex-wrap">
        <div class="uk-label uk-label-muted" v-for="value in values">
          {{ value }}
          <a href="" @click.prevent="removeValue(value)" class="uk-icon-link"><icon name="x" /></a>
        </div>
        <button class="uk-button uk-button-small uk-button-light" :id="domID" @click.prevent="selectModalOpen = true" :disabled="!referencedView">
          <icon name="plus" />
          {{ $t('field.reference.select') }}
        </button>
      </div>
      <p v-if="field.description" class="uk-text-meta uk-margin-small-top">{{ field.description }}</p>
      <div v-if="!referencedView" class="uk-alert uk-alert-warning">{{ $t('field.reference.missing_view_warning') }}</div>
    </div>

    <modal v-if="referencedView && selectModalOpen" @hide="selectModalOpen = false" :title="$t('field.reference.modal.headline')">
      <component :is="$unite.getViewType(referencedView.viewType)" :view="referencedView" :initial-selection="this.val" :select="field.list_of ? 'MULTIPLE' : 'SINGLE'" @select="onSelect" />
    </modal>

  </div>
</template>
<script>
  import _abstract from "./_abstract";
  import Icon from "../../Icon";
  import Modal from "../../Modal";

  export default {
      extends: _abstract,
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
      components: { Icon, Modal },
      data(){
          return {
              selectModalOpen: false,
          };
      },
      computed: {
          referencedView() {

              // TODO: Allow to configure the adminView to use.

              let referencedView = Object.values(this.$unite.adminViews).filter((view) => {
                  return view.type === this.field.returnType;
              });

              return referencedView.length > 0 ? referencedView[0] : null;
          }
      },
      methods: {
          onSelect(values) {
              this.val = values;
              this.selectModalOpen = false;
          }
      }
  }
</script>
