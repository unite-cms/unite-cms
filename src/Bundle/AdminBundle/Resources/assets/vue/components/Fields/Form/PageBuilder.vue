<template>
    <form-row :domID="domID" :field="field" :alerts="violations">
        <multi-field :field="field" :val="val" @addRow="val.push('')" @removeRow="removeByKey" v-slot:default="multiProps">
            <editor-floating-menu :editor="editorForKey(multiProps.rowKey || 0)" v-slot="{ commands, isActive, menu }">
                <div class="editor__floating-menu" :class="{ 'is-active': menu.isActive }" :style="`top: ${menu.top}px`">
                    <button @click.prevent="insertCustomBlock(editorForKey(multiProps.rowKey || 0), cBlock)" type="button" class="uk-button uk-button-light" v-for="cBlock in customBlocks">
                        {{ cBlock.rawType.name }}
                    </button>
                </div>
            </editor-floating-menu>
            <editor-menu-bubble :editor="editorForKey(multiProps.rowKey || 0)" v-slot="{ commands, isActive, menu }" :keep-in-bounds="true">
                <div class="editor__bubble-menu" :class="{ 'is-active': menu.isActive }" :style="`left: ${menu.left}px; bottom: ${menu.bottom}px;`">
                    <component v-for="(menuItem, key) in menuItems" :key="key" :editor="editorForKey(multiProps.rowKey || 0)" :is="menuItem" :commands="commands" :is-active="isActive" :field="field" />
                </div>
            </editor-menu-bubble>
            <editor-content :editor="editorForKey(multiProps.rowKey || 0)" />
        </multi-field>
    </form-row>
</template>
<script>
    import { Node } from 'tiptap';
    import { Node as PNode } from 'prosemirror-model';
    import _abstract from "./_abstract";
    import FormRow from './_formRow';
    import MultiField from './_multiField';
    import { Editor, EditorContent, EditorMenuBar, EditorFloatingMenu, EditorMenuBubble } from 'tiptap'
    import TipTap from "../../../plugins/tiptap";
    import Icon from "../../Icon";
    import UIkit from 'uikit';

    export default {

        // Static query methods for unite system.
        queryData(field, unite, depth) { return `
            ${field.id} { HTML, JSON }`
        },
        normalizeQueryData(queryData, field, unite) { return queryData; },
        normalizeMutationData(formData, field, unite) { return formData; },

        // Vue properties for this component.
        extends: _abstract,
        components: {Icon, MultiField, FormRow, EditorContent, EditorMenuBar, EditorFloatingMenu, EditorMenuBubble },
        data() {
            return {
                editors: [],
                customBlocks: [],
            }
        },
        watch: {
            values: {
                handler(values) {
                    values.forEach((value, key) => {
                        let editor = this.editorForKey(key);
                        if(editor.getJSON() !== value.JSON) {
                            editor.setContent(JSON.parse(value.JSON));
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

                    this.createCustomBlocks();
                    let customBlocks = this.customBlocks.map((type) => {
                        return new class extends Node {
                            get name() { return type.rawType.id }
                            get schema() {
                                return {
                                    group: 'block',
                                    atom: true,
                                    defining: false,
                                    draggable: true,
                                    selectable: true,
                                    isolating: true,
                                    parseDOM: [
                                        {
                                            tag: 'div',
                                            attrs: {
                                                'data-custom-block-id': type.rawType.id
                                            },
                                        },
                                    ],
                                    toDOM: () => ['div', {class: 'special-placeholder', 'data-custom-block-id': type.rawType.id}],
                                }
                            }
                            get view() {
                                return {
                                    methods: {
                                        openConfig() {
                                            UIkit.offcanvas(this.$refs.configCanvas).show();
                                        }
                                    },
                                    template: '<div><div class="uk-placeholder uk-padding uk-margin-remove">' + type.rawType.name + '<button type="button" @click="openConfig">config</button></div><div ref="configCanvas" uk-offcanvas="flip: true; mode: push"><div class="uk-offcanvas-bar"><button class="uk-offcanvas-close" type="button" uk-close></button>CONFIG</div></div></div>'
                                }
                            }
                        };
                    });

                    this.editors[key] = new Editor({
                        extensions: [...TipTap.buildExtensionsForField(this.field), ...customBlocks],
                        onUpdate: ( { state, getHTML, getJSON, transaction } ) => {
                            this.setValue([{
                                HTML: getHTML(),
                                JSON: JSON.stringify(getJSON()),
                            }], key);
                        },
                        editorProps: {
                            content: this.values[key] || '',
                            attributes: {
                                class: 'uk-textarea uk-position-relative uk-height-large',
                                required: this.field.required,
                                id: this.domID,
                            }
                        },
                    });
                }
                return this.editors[key];
            },
            createCustomBlocks() {

                let allowedTypes = [];

                if(this.field.config.customBlocks) {
                    let unionType = this.$unite.rawTypes.filter((rawType) => {
                        return rawType.kind === 'UNION' && rawType.name === this.field.config.customBlocks;
                    });
                    if(unionType.length > 0) {
                        allowedTypes = unionType[0].possibleTypes.map((type) => { return type.name; });
                    }
                }

                let blockTypes = this.$unite.rawTypes.filter((rawType) => {

                    if(rawType.name === 'UnitePageBuilderBlock') {
                        return;
                    }

                    if(allowedTypes.length > 0 && allowedTypes.indexOf(rawType.name) < 0) {
                        return;
                    }

                    return (rawType.interfaces || []).filter((i) => {
                        return i.name === 'UnitePageBuilderBlockType';
                    }).length > 0;
                });

                this.customBlocks = blockTypes.map((blockType) => {

                    let matchedView = Object.values(this.$unite.adminViews).filter(view => {
                        return view.viewType === 'EmbeddedAdminView' && view.type === blockType.name;
                    });

                    return {
                        rawType: blockType,
                        adminView: matchedView.length > 0 ? matchedView[0] : null,
                    };
                });

            },
            insertCustomBlock(editor, adminView) {
                let node = PNode.fromJSON(editor.schema, {type: adminView.id});
                editor.view.dispatch(
                    editor.state.tr.insert(
                        editor.state.selection.$anchor.pos,
                        node
                    )
                );
            }
        },
        beforeDestroy() {
            this.editors.forEach((editor) => {
                editor.destroy();
            });
        },
    }
</script>
<style lang="scss">
    .editor__floating-menu,
    .editor__bubble-menu {
        position: absolute;
        opacity: 0;
        z-index: 10;

        &.is-active {
            opacity: 1;
        }

    }
</style>