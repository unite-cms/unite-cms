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

import { createGenericMenuItem } from "./vue/components/TipTap/MenuItems/_basic";

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
