
import { messages, dateTimeFormats, i18n } from "../plugins/i18n";

// Set en part of messages.
messages.en = {
    general: {
        back: 'Back'
    },
    navigation: {
        content_types: {
            headline: 'Content Types'
        }
    },
    schema: {
        save: 'Save',
        add: 'Add schema file',
        compose: 'Create new schema file (filename without .graphql)',
        rename: 'Update filename (without .graphql)',
        delete: 'Do you really want to delete schema file "{filename}"?',
    },
    content: {
        list: {
            field: {
                fallback_warning: 'Missing list field type "{type}" for field "{id}".',
            },
            view: {
                fallback_warning: 'Missing view type "{viewType}" for view with id "{id}".',
            },
            actions: {
                create: 'Create',
                update: 'Update',
                delete: 'Delete',
                revert: 'Revert',
                translate: 'Translate',
            }
        },
        form: {
            field: {
                fallback_warning: 'Missing form field type "{type}" for field "{id}".',
            },
        },
        create: {
            headline: 'Create {name}',
            actions: {
                create: 'Save'
            },
            success: 'New "{name}" content was created.',
        },
        update: {
            headline: 'Update {name}',
            actions: {
                update: 'Save'
            },
            success: '"{name}" content was updated.',
        }
    }
};

// Set en part of dateTimeFormats.
dateTimeFormats.en = {
    full: {
        year: '2-digit', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', seconds: '2-digit', hour12: false
    }
};

// If we include this file, set the language to "en".
i18n.locale = 'en';
