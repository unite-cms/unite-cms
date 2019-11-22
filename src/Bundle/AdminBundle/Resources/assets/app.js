
import Vue from 'vue';
import State from './vue/state';
import apolloProvider from './vue/plugins/apollo';
import { Unite, VueUnite } from './vue/plugins/unite';

import IdList from "./vue/components/Fields/List/Id";
import TextList from "./vue/components/Fields/List/Text";
import EmailList from "./vue/components/Fields/List/Text";
import IntegerList from "./vue/components/Fields/List/Text";
import ChoiceList from "./vue/components/Fields/List/Choice";
import ReferenceList from "./vue/components/Fields/List/Reference";
import SequenceList from "./vue/components/Fields/List/Sequence";
import DateList from "./vue/components/Fields/List/Date";
import DateTimeList from "./vue/components/Fields/List/DateTime";

import TextForm from "./vue/components/Fields/Form/Text";
import EmailForm from "./vue/components/Fields/Form/Email";
import IntegerForm from "./vue/components/Fields/Form/Integer";
import ChoiceForm from "./vue/components/Fields/Form/Choice";
import ReferenceForm from "./vue/components/Fields/Form/Reference";
import SequenceForm from "./vue/components/Fields/Form/Sequence";
import DateForm from "./vue/components/Fields/Form/Date";
import DateTimeForm from "./vue/components/Fields/Form/DateTime";

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
Unite.$emit('registerListFieldType', 'id', IdList);
Unite.$emit('registerListFieldType', 'text', TextList);
Unite.$emit('registerListFieldType', 'email', EmailList);
Unite.$emit('registerListFieldType', 'integer', IntegerList);
Unite.$emit('registerListFieldType', 'choice', ChoiceList);
Unite.$emit('registerListFieldType', 'reference', ReferenceList);
Unite.$emit('registerListFieldType', 'sequence', SequenceList);
Unite.$emit('registerListFieldType', 'date', DateList);
Unite.$emit('registerListFieldType', 'dateTime', DateTimeList);

Unite.$emit('registerFormFieldType', 'text', TextForm);
Unite.$emit('registerFormFieldType', 'email', EmailForm);
Unite.$emit('registerFormFieldType', 'integer', IntegerForm);
Unite.$emit('registerFormFieldType', 'choice', ChoiceForm);
Unite.$emit('registerFormFieldType', 'reference', ReferenceForm);
Unite.$emit('registerFormFieldType', 'sequence', SequenceForm);
Unite.$emit('registerFormFieldType', 'date', DateForm);
Unite.$emit('registerFormFieldType', 'dateTime', DateTimeForm);

Unite.$emit('registerViewType', 'TableAdminView', Table);
Unite.$emit('registerViewType', 'SettingsAdminView', Settings);

Vue.use(VueUnite);
Unite.$apolloProvider = apolloProvider;
