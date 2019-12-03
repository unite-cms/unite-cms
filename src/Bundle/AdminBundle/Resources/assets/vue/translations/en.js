
import { messages, dateTimeFormats, i18n } from "../plugins/i18n";
import { en } from 'vuejs-datepicker/dist/locale'

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
                fallback_warning: 'Missing list field type "{fieldType}" for field "{id}".',
            },
            view: {
                fallback_warning: 'Missing view type "{viewType}" for view with id "{id}".',
            },
            actions: {
                create: 'Create',
                update: 'Update',
                delete: 'Delete',
                permanent_delete: 'Permanent delete',
                revert: 'Manage Revisions',
                recover: 'Recover',
                toggle_deleted: 'Toggle deleted content'
            },
            selection: {
                select: 'Select',
                confirm: 'Confirm selection of {count}'
            }
        },
        form: {
            field: {
                fallback_warning: 'Missing form field type "{fieldType}" for field "{id}".',
            },
        },
        create: {
            headline: 'Create "{contentTitle}"',
            actions: {
                submit: 'Save'
            },
            success: '"{contentTitle}" was created.',
        },
        update: {
            headline: 'Update "{contentTitle}"',
            actions: {
                submit: 'Save'
            },
            success: '"{contentTitle}" was updated.',
        },
        delete: {
            headline: 'Move "{contentTitle}" to trash',
            message: 'Do you really want to move "{contentTitle}" to trash?',
            actions: {
                submit: 'Move to trash'
            },
            success: '"{contentTitle}" content was moved to trash.',
        },
        recover: {
            headline: 'Recover "{contentTitle}" from trash',
            message: 'Do you really want to recover "{contentTitle}" from trash?',
            actions: {
                submit: 'Recover from trash'
            },
            success: '"{contentTitle}" was recovered.',
        },
        revert: {
            headline: 'Revisions of "{contentTitle}"',
            success: '"{contentTitle}" was reverted to {version}. revision.',
            confirm: 'Do you really want to revert "{contentTitle}" to version {version}?',
            label: {
                current: 'Current'
            },
            header: {
                version: '#',
                operation: 'Operation',
                meta: 'Meta'
            },
            actions: {
                revert: 'Revert content to this version'
            }
        },
        permanent_delete: {
            headline: 'Remove "{contentTitle}"',
            message: 'Do you really want to permanently delete "{contentTitle}"? Be careful, you cannot undo this action!',
            actions: {
                submit: 'Permanently remove'
            },
            success: '"{contentTitle}" was removed.',
        },
    },
    field: {
        reference: {
            missing_view_warning: 'No admin view for the referenced content of this field was found. Please add one to your schema!',
            modal: {
                headline: 'Select a content to reference'
            }
        },
        reference_of: {
            missing_view_warning: 'No admin view for the referenced content of this field was found. Please add one to your schema!',
            no_content_id: 'A reference to this field is only possible after creation.',
            modal: {
                headline: 'Referenced {name} of "{contentTitle}"'
            }
        },
        geoLocation: {
            placeholder: {
                stairs_number: 'Stairs',
                door_number: 'Door'
            },
            modal: {
                headline: '{display_name}'
            }
        },

        embedded: {
            missing_view_warning: 'No admin view for the embedded content of this field was found. Please add one to your schema!',
            confirm: {
                clear_union_selection: 'Do you really want to select another {view.name} type? Your already entered vales for this field will be deleted.'
            }
        },

        date: en
    }
};

// Set en part of dateTimeFormats.
dateTimeFormats.en = {
    full: {
        year: '2-digit', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', seconds: '2-digit', hour12: false
    },
    date: {
        year: '2-digit', month: '2-digit', day: '2-digit'
    }
};

// If we include this file, set the language to "en".
i18n.locale = 'en';
