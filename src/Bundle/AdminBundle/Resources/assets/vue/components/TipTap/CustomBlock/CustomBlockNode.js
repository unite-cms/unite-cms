
import { Node } from 'tiptap'
import CustomBlock from "./CustomBlock";
import { Unite } from '../../../plugins/unite';

export function allowChildren(type) {

    let contentField = type.fields.filter((field) => {
        return field.name === 'content' && field.type.kind === 'LIST' && field.type.ofType.kind === 'NON_NULL';
    });

    if(contentField.length === 0) {
        return false;
    }

    contentField = contentField[0];

    let childrenType = contentField.type.ofType.ofType.name;

    if(childrenType === 'UnitePageBuilderBlockType') {
        return ['UnitePageBuilderBlockType'];
    }

    let rawType = Unite.rawTypes.filter(rType => rType.name === childrenType);

    if(rawType.length === 0) {
        return false;
    }

    if(rawType[0].kind === 'OBJECT') {
        if(rawType[0].interfaces.filter(i => i.name === 'UnitePageBuilderBlockType').length === 0) {
            return false;
        }
        return [rawType[0].name];
    }

    return false;
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

        let content = null;
        if(withChildren) {
            content = withChildren.map((type) => {
                if(type === 'UnitePageBuilderBlockType') {
                    return 'block*';
                } else {
                    return `Unite${type}*`;
                }
            }).join('|');
        }

        return {
            attrs: attrs,
            group: 'block',
            content: content,
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
