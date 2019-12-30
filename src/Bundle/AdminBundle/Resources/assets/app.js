
import Vue from 'vue';
import State from './vue/state';
import apolloProvider from './vue/plugins/apollo';
import { Unite, VueUnite } from './vue/plugins/unite';

import IdList from "./vue/components/Fields/List/Id";
import TextList from "./vue/components/Fields/List/Text";
import EmailList from "./vue/components/Fields/List/Text";
import IntegerList from "./vue/components/Fields/List/Text";
import FloatList from "./vue/components/Fields/List/Text";
import TextareaList from "./vue/components/Fields/List/Textarea";
import WysiwygList from "./vue/components/Fields/List/Wysiwyg";
import ChoiceList from "./vue/components/Fields/List/Choice";
import CheckboxList from "./vue/components/Fields/List/Checkbox";
import SequenceList from "./vue/components/Fields/List/Sequence";
import DateList from "./vue/components/Fields/List/Date";
import DateTimeList from "./vue/components/Fields/List/DateTime";
import GeoLocationList from "./vue/components/Fields/List/GeoLocation";
import ReferenceList from "./vue/components/Fields/List/Reference";
import ReferenceOfList from "./vue/components/Fields/List/ReferenceOf";
import EmbeddedList from "./vue/components/Fields/List/Reference";

import TextForm from "./vue/components/Fields/Form/Text";
import EmailForm from "./vue/components/Fields/Form/Email";
import IntegerForm from "./vue/components/Fields/Form/Integer";
import FloatForm from "./vue/components/Fields/Form/Float";
import TextareaForm from "./vue/components/Fields/Form/Textarea";
import WysiwygForm from "./vue/components/Fields/Form/Wysiwyg";
import ChoiceForm from "./vue/components/Fields/Form/Choice";
import CheckboxForm from "./vue/components/Fields/Form/Checkbox";
import SequenceForm from "./vue/components/Fields/Form/Sequence";
import DateForm from "./vue/components/Fields/Form/Date";
import DateTimeForm from "./vue/components/Fields/Form/DateTime";
import GeoLocationForm from "./vue/components/Fields/Form/GeoLocation";
import ReferenceForm from "./vue/components/Fields/Form/Reference";
import ReferenceOfForm from "./vue/components/Fields/Form/ReferenceOf";
import EmbeddedForm from "./vue/components/Fields/Form/Embedded";

import Table from "./vue/components/Views/Table";
import Settings from "./vue/components/Views/Settings";
import Dashboard from "./vue/components/Views/Dashboard";


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
Unite.$emit('registerListFieldType', 'float', FloatList);
Unite.$emit('registerListFieldType', 'textarea', TextareaList);
Unite.$emit('registerListFieldType', 'wysiwyg', WysiwygList);
Unite.$emit('registerListFieldType', 'choice', ChoiceList);
Unite.$emit('registerListFieldType', 'boolean', CheckboxList);
Unite.$emit('registerListFieldType', 'sequence', SequenceList);
Unite.$emit('registerListFieldType', 'date', DateList);
Unite.$emit('registerListFieldType', 'dateTime', DateTimeList);
Unite.$emit('registerListFieldType', 'geoLocation', GeoLocationList);
Unite.$emit('registerListFieldType', 'reference', ReferenceList);
Unite.$emit('registerListFieldType', 'referenceOf', ReferenceOfList);
Unite.$emit('registerListFieldType', 'embedded', EmbeddedList);

Unite.$emit('registerFormFieldType', 'text', TextForm);
Unite.$emit('registerFormFieldType', 'email', EmailForm);
Unite.$emit('registerFormFieldType', 'integer', IntegerForm);
Unite.$emit('registerFormFieldType', 'float', FloatForm);
Unite.$emit('registerFormFieldType', 'textarea', TextareaForm);
Unite.$emit('registerFormFieldType', 'wysiwyg', WysiwygForm);
Unite.$emit('registerFormFieldType', 'choice', ChoiceForm);
Unite.$emit('registerFormFieldType', 'boolean', CheckboxForm);
Unite.$emit('registerFormFieldType', 'sequence', SequenceForm);
Unite.$emit('registerFormFieldType', 'date', DateForm);
Unite.$emit('registerFormFieldType', 'dateTime', DateTimeForm);
Unite.$emit('registerFormFieldType', 'geoLocation', GeoLocationForm);
Unite.$emit('registerFormFieldType', 'reference', ReferenceForm);
Unite.$emit('registerFormFieldType', 'referenceOf', ReferenceOfForm);
Unite.$emit('registerFormFieldType', 'embedded', EmbeddedForm);

Unite.$emit('registerViewType', 'TableAdminView', Table);
Unite.$emit('registerViewType', 'SettingsAdminView', Settings);
Unite.$emit('registerViewType', 'DashboardAdminView', Dashboard);

Vue.use(VueUnite);
Unite.$apolloProvider = apolloProvider;
