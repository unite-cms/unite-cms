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
    History,
    TrailingNode,
} from 'tiptap-extensions';

import { createGenericMenuItem, basicMenuItem } from "./vue/components/TipTap/MenuItems/_basic";

TipTap.$emit('registerExtension', () => { return new History(); });
TipTap.$emit('registerExtension', () => { return new TrailingNode(); });
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
import UIkit from 'uikit';

import { Fragment, Node as PNode } from 'prosemirror-model';
import { Selection, NodeSelection } from 'prosemirror-state';


class Row extends Node {
    get name() { return 'row' }
    get schema() {
        return {
            content: 'column+',
            group: 'block',
            marks: "",
            atom: false,
            selectable: true,
            defining: true,
            draggable: false,
            isolating: true,
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
            template: '<div class="uk-flex" ref="content"></div>'
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
            selectable: false,
            isolating: true,
            parseDOM: [
                {
                    tag: 'div',
                    attrs: {
                        class: 'uk-flex-1'
                    },
                },
            ],
            toDOM: () => ['div', { class: 'uk-flex-1' }, 0],
        }
    }
    get view() {
        return {
            props: ['node', 'updateAttrs', 'view'],
            template: '<div class="uk-flex-1 uk-padding uk-placeholder uk-margin-remove" ref="content"></div>'
        }
    }
}

class Placeholder extends Node {
    get name() { return 'placeholder' }
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
                        class: 'special-placeholder'
                    },
                },
            ],
            toDOM: () => ['div', { class: 'special-placeholder' }],
        }
    }
    get view() {
        return {
            template: '<div class="uk-placeholder uk-padding uk-margin-remove">This is just a placeholder</div>'
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
            UIkit.modal.prompt('Columns:', '2').then((numb) => {

                let content = [];
                for(let i = 0; i < parseInt(numb); i++) {
                    content.push({ type: 'column', content: [{ type: 'paragraph' }] });
                }

                let node = PNode.fromJSON(this.editor.schema, {
                    type: 'row',
                    content: content
                });
                this.editor.view.dispatch(
                    this.editor.state.tr.insert(
                        this.editor.state.selection.$anchor.pos,
                        node
                    )
                );
            })
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
