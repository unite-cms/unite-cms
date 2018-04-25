
import Vue from "vue";
import vueCustomElement from 'vue-custom-element';
import Wysiwyg from "./vue/field/Wysiwyg.vue";

// Use VueCustomElement
Vue.use(vueCustomElement);

// Override quill js toolbar icons and use feather icons.
//import feather from 'feather-icons';
//import Quill from 'quill';
/*let icons = Quill.import('ui/icons');
console.log(icons);
let mapping = {
    'bold': 'bold',
    'italic': 'italic',
    'underline': 'underline',
    //'strike': 'bold',
    //'blockquote': 'bold',
    //'header.1': 'bold',
    //'header.2': 'bold',
    //'list.bullet': 'bold',
    //'list.ordered': 'bold',
    //'indent.+1': 'bold',
    //'indent.-1': 'bold',
    //'script.sub': 'bold',
    //'script.super': 'bold',
    //'clean': '',
    'link': 'link',
};

for(let source in mapping) {

    let icon = feather.icons[mapping[source]].toSvg({ width: 20, height: 20 });

    if(source.split('.').length > 1) {
        icons[source.split('.')[0]][source.split('.')[1]] = icon;
    } else {
        icons[source] = icon;
    }
}*/

Vue.customElement('unite-cms-wysiwyg-field', Wysiwyg);
