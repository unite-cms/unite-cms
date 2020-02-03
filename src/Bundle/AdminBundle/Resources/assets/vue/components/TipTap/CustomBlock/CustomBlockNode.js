
import { Node } from 'tiptap'
import CustomBlock from "./CustomBlock";
import { Unite } from '../../../plugins/unite';

export function allowChildren(type) {
    return type.fields.filter((field) => {
        return field.type.kind === 'LIST'
            && field.type.ofType.kind === 'NON_NULL'
            && field.type.ofType.ofType.name === 'PageBuilderBlocks';
    }).length > 0;
}

export function isFieldable(type) {
    return type.interfaces.filter((interf) => {
        return interf.name === 'UniteFieldable'
    }).length > 0;
}

export default class CustomBlockNode extends Node {
    get name() { return 'Unite' + this.options.type.name }

    get schema() {
        let withChildren = allowChildren(this.options.type);
        let typeIsFieldable = isFieldable(this.options.type);
        let toDOM = ['div', {'data-custom-unite-block': this.options.type.name}];
        if(withChildren) {
            toDOM.push(0);
        }

        let attrs = {};

        if(typeIsFieldable) {
            let views = Object.values(Unite.adminViews).filter((adminView) => {
                return adminView.type === this.options.type.name;
            });
            if(views.length > 0) {
                attrs = views[0].normalizeMutationData({});
                Object.keys(attrs).forEach((key) => { attrs[key] = {}; });
            }
        }

        return {
            attrs: attrs,
            group: 'block',
            content: withChildren ? 'block*' : null,
            atom: !withChildren,
            defining: false,
            draggable: true,
            selectable: false,
            isolating: true,
            parseDOM: [
                {
                    tag: 'div',
                    attrs: {
                        'data-custom-unite-block': this.options.type.name
                    },
                },
            ],
            toDOM() { return toDOM; },
        }
    }
    get view() {

        const customType = this.options.type;

        return {
            extends: CustomBlock,
            computed: {
                customType() { return customType; }
            }
        }
    }
};
