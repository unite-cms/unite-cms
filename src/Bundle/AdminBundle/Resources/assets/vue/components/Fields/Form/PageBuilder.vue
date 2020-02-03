<template>
    <form-row :domID="domID" :field="field" :alerts="violations">
        <multi-field :field="field" :val="val" @addRow="val.push('')" @removeRow="removeByKey" v-slot:default="multiProps">

            <editor :value="values[multiProps.rowKey || 0]" @input="setValue(arguments, multiProps.rowKey)" :extensions="extensions" :inline-commands="inlineCommands" :block-commands="blockCommands" />

        </multi-field>
    </form-row>
</template>
<script>

    import _abstract from "./_abstract";
    import FormRow from './_formRow';
    import MultiField from './_multiField';
    import Editor from "../../TipTap/Editor";
    import { BoldCommand, ItalicCommand, Divider } from "../../TipTap/Command/InlineMenuCommand";
    import { ParagraphCommand, BlockQuoteCommand, CodeBlockCommand, OrderedListCommand, BulletListCommand } from "../../TipTap/Command/BlockMenuCommand";
    import InlineBlockCommand from "../../TipTap/Command/InlineBlockCommand";
    import InlineListCommand from "../../TipTap/Command/InlineListCommand";

    import {
        Blockquote,
        CodeBlock,
        HardBreak,
        Heading,
        OrderedList,
        BulletList,
        ListItem,
        Bold,
        Italic,
        History,
        TrailingNode,
    } from 'tiptap-extensions'

    export default {

        // Static query methods for unite system.
        queryData(field, unite, depth) { return `
            ${field.id} { HTML, JSON }`
        },
        normalizeQueryData(queryData, field, unite) { return queryData; },
        normalizeMutationData(formData, field, unite) { return formData; },

        // Vue properties for this component.
        extends: _abstract,
        components: { Editor, MultiField, FormRow },

        data() {
            return {
                extensions: [
                    new Heading({ levels: [1,2,3,4,5,6] }),
                    new HardBreak(),
                    new Bold(),
                    new Italic(),
                    new History(),
                    new TrailingNode({ node: 'paragraph', notAfter: ['paragraph'], }),
                    new Blockquote(),
                    new CodeBlock(),
                    new OrderedList(),
                    new BulletList(),
                    new ListItem(),
                ],
            }
        },

        computed: {
            inlineCommands() {
                return [
                    BoldCommand,
                    ItalicCommand,
                    Divider,
                    {
                        component: InlineBlockCommand,
                        config: { levels: [1,2,3,4,5,6] }
                    },
                    InlineListCommand,
                ];
            },
            blockCommands() {
                return [
                    ParagraphCommand,
                    BlockQuoteCommand,
                    CodeBlockCommand,
                    OrderedListCommand,
                    BulletListCommand,
                ];
            }
        }

        /*data() {
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
        },*/
    }
</script>
