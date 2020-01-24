import TipTap from "./vue/plugins/tiptap";
import {
    HardBreak,
    Heading,
    HorizontalRule,
    OrderedList,
    BulletList,
    ListItem,
    Bold,
    Code,
    Blockquote,
    Italic,
    Link,
    Underline,
} from 'tiptap-extensions';

import { createGenericMenuItem, basicMenuItem } from "./vue/components/TipTap/MenuItems/_basic";

TipTap.$emit('registerExtension', () => { return new HardBreak(); });
TipTap.$emit('registerExtension', () => { return new Heading({ levels: [1, 2, 3, 4, 5, 6] }); });
TipTap.$emit('registerExtension', () => { return new HorizontalRule(); });
TipTap.$emit('registerExtension', () => { return new OrderedList(); });
TipTap.$emit('registerExtension', () => { return new BulletList(); });
TipTap.$emit('registerExtension', () => { return new ListItem(); });
TipTap.$emit('registerExtension', () => { return new Bold(); });
TipTap.$emit('registerExtension', () => { return new Blockquote(); });
TipTap.$emit('registerExtension', () => { return new Italic(); });
TipTap.$emit('registerExtension', () => { return new Underline(); });

TipTap.$emit('registerMenuItem', createGenericMenuItem('bold', 'bold'));
TipTap.$emit('registerMenuItem', createGenericMenuItem('italic', 'italic'));
TipTap.$emit('registerMenuItem', createGenericMenuItem('underline', 'underline'));

import { Node } from 'tiptap';
import Icon from "./vue/components/Icon";

import { Fragment, Node as PNode } from 'prosemirror-model';
import { Selection, NodeSelection } from 'prosemirror-state';


class Row extends Node {
    get name() { return 'row' }
    get schema() {
        return {
            content: 'column+',
            group: 'block',
            selectable: true,
            defining: true,
            draggable: false,
            parseDOM: [
                {
                    tag: 'div',
                    attrs: {
                        class: 'uk-flex'
                    },
                },
            ],
            toDOM: () => ['div', { class: 'uk-flex' }, 0],
        }
    }
    get view() {
        return {
            props: ['node', 'updateAttrs', 'view'],
            methods: {
                onAdd() {
                    let content = this.node.content.append(
                        Fragment.fromJSON(this.view.state.schema, [{
                            type: "column", content: [{ type: 'paragraph' }]
                        }])
                    );

                    console.log(this.view.state.selection);

                    /*this.view.dispatch(
                        this.view.state.tr.replaceWith(
                            Selection.atStart(this.node).$anchor.pos,
                            Selection.atEnd(this.node).$anchor.pos,
                            content
                        )
                    );*/
                }
            },
            template: '<div><div class="uk-flex uk-padding uk-placeholder" ref="content"></div><button type="button" @click.prevent="onAdd">+</button></div>'
        }
    }

}

class Column extends Node {
    get name() { return 'column' }
    get schema() {
        return {
            content: 'block+',
            group: 'column',
            defining: true,
            draggable: false,
            parseDOM: [
                {
                    tag: 'div',
                    attrs: {
                        class: 'uk-flex-1 uk-padding uk-placeholder'
                    },
                },
            ],
            toDOM: () => ['div', { class: 'uk-flex-1 uk-padding uk-placeholder' }, 0],
        }
    }
}

class Placeholder extends Node {
    get name() { return 'placeholder' }
    get schema() {
        return {
            group: 'block',
            defining: false,
            draggable: true,
            selectable: false,
            parseDOM: [
                {
                    tag: 'div',
                    attrs: {
                        class: 'special-placeholder'
                    },
                },
            ],
            toDOM: () => ['div', { class: 'special-placeholder' }, 0],
        }
    }
    get view() {
        return {
            template: '<div class="uk-placeholder uk-padding">This is just a placeholder</div>'
        }
    }
}

TipTap.$emit('registerExtension', () => { return new Row(); });
TipTap.$emit('registerExtension', () => { return new Column(); });
TipTap.$emit('registerExtension', () => { return new Placeholder(); });

TipTap.$emit('registerMenuItem', {
    extends: basicMenuItem,
    props: {
        editor: Object,
    },
    methods: {
        onClick() {
            let node = PNode.fromJSON(this.editor.schema, {
                type: 'row',
                content: [
                    { type: 'column', content: [{ type: 'paragraph' }] },
                    { type: 'column', content: [{ type: 'paragraph' }] },
                ]
            });
            this.editor.view.dispatch(
                this.editor.state.tr.insert(
                    this.editor.state.selection.$anchor.pos,
                    node
                )
            );
        }
    }
});

TipTap.$emit('registerMenuItem', {
    extends: basicMenuItem,
    props: {
        editor: Object,
    },
    computed: { icon() { return 'star'; } },
    methods: {
        onClick() {
            let node = PNode.fromJSON(this.editor.schema, {type: 'placeholder'});
            this.editor.view.dispatch(
                this.editor.state.tr.insert(
                    this.editor.state.selection.$anchor.pos,
                    node
                )
            );
        }
    }
});


TipTap.$emit('registerMenuItem', {
    extends: basicMenuItem,
    props: {
        editor: Object,
    },
    methods: {
        onClick() {
            let fragment = Fragment.fromJSON(this.editor.schema, this.editor.getJSON().content);
            this.editor.view.dispatch(
                this.editor.state.tr.insert(
                    this.editor.state.selection.$anchor.pos,
                    fragment
                )
            );
        }
    }
});
