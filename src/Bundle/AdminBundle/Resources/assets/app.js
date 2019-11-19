
import Vue from 'vue';
import State from './vue/state';
import apolloProvider from './vue/plugins/apollo';
import { Unite, VueUnite } from './vue/plugins/unite';

import TextList from "./vue/components/Fields/List/Text";
import TextForm from "./vue/components/Fields/Form/Text";
import ChoiceList from "./vue/components/Fields/List/Choice";
import ChoiceForm from "./vue/components/Fields/Form/Choice";

import Table from "./vue/components/Views/Table";
import Settings from "./vue/components/Views/Settings";


////////// INIT UIKIT //////////
import UIkit from 'uikit';



////////// INIT STATE //////////
Object.keys(State).forEach((state) => {
    State[state].$apolloProvider = apolloProvider;
    State[state].$emit('load');
});



////////// INIT UNITE //////////
Unite.$emit('registerListFieldType', 'text', TextList);
Unite.$emit('registerFormFieldType', 'text', TextForm);
Unite.$emit('registerListFieldType', 'choice', ChoiceList);
Unite.$emit('registerFormFieldType', 'choice', ChoiceForm);
Unite.$emit('registerViewType', 'TableAdminView', Table);
Unite.$emit('registerViewType', 'SettingsAdminView', Settings);

Vue.use(VueUnite);
Unite.$apolloProvider = apolloProvider;
