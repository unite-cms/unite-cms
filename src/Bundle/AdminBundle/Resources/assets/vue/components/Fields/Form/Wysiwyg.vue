<template>
  <form-row :domID="domID" :field="field" :alerts="violations">
    <multi-field :field="field" :val="val" @addRow="val.push('')" @removeRow="removeByKey" v-slot:default="multiProps">
      <editor-menu-bar :editor="editorForKey(multiProps.rowKey || 0)" v-slot="{ commands, isActive }">
        <ul class="uk-iconnav">
          <component v-for="(menuItem, key) in menuItems" :key="key" :is="menuItem" :commands="commands" :is-active="isActive" :field="field" />
        </ul>
      </editor-menu-bar>
      <editor-content :editor="editorForKey(multiProps.rowKey || 0)" />
    </multi-field>
  </form-row>
</template>
<script>
  import _abstract from "./_abstract";
  import FormRow from './_formRow';
  import MultiField from './_multiField';
  import { Editor, EditorContent, EditorMenuBar } from 'tiptap'
  import TipTap from "../../../plugins/tiptap";

  export default {

      // Static query methods for unite system.
      queryData(field, unite, depth) { return field.id },
      normalizeQueryData(queryData, field, unite) { return queryData; },
      normalizeMutationData(formData, field, unite) { return formData; },

      // Vue properties for this component.
      extends: _abstract,
      components: { MultiField, FormRow, EditorContent, EditorMenuBar },
      data() {
          return {
              editors: [],
          }
      },
      watch: {
          values: {
              handler(values) {
                  values.forEach((value, key) => {
                      let editor = this.editorForKey(key);
                      if(editor.getHTML() !== value) {
                          editor.setContent(value);
                      }
                  });
              }
          },
      },
      computed: {
          menuItems() {
              return TipTap.menuItems;
          }
      },
      methods: {
          editorForKey(key) {
              if(!this.editors[key]) {
                  this.editors[key] = new Editor({
                      extensions: TipTap.buildExtensionsForField(this.field),
                      onUpdate: ( { state, getHTML, getJSON, transaction } ) => {
                          this.setValue([getHTML()], key);
                      },
                      editorProps: {
                          content: this.values[key] || '',
                          attributes: {
                              class: 'uk-textarea',
                              required: this.field.required,
                              id: this.domID,
                          }
                      },
                  });
              }
              return this.editors[key];
          },
      },
      beforeDestroy() {
          this.editors.forEach((editor) => {
              editor.destroy();
          });
      },
  }
</script>
