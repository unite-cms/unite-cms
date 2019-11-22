
import { messages, i18n } from "../plugins/i18n";
import { de } from 'vuejs-datepicker/dist/locale'

// Set de part of messages.
messages.de = {
    navigation: {
        content_types: {
            headline: 'Inhalte'
        }
    },
    field: {
        date: de
    }
};

// If we include this file, set the language to "de".
i18n.locale = 'de';
