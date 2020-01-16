
import { messages, dateTimeFormats, i18n } from "../plugins/i18n";
import { de } from 'vuejs-datepicker/dist/locale'

// Set de part of messages.
messages.de = {
    general: {
        back: 'Zurück'
    },
    navigation: {
        content_types: {
            headline: 'Inhaltstypen'
        }
    },
    schema: {
        save: 'Speichern',
        add: 'Schema Datei hinzufügen',
        compose: 'Neue Schema Datei erstellen (Dateiname ohne .graphql)',
        rename: 'Dateiname ändern (ohne .graphql)',
        delete: 'Möchtest du die Schema Datei "{filename}" wirklich löschen?',
    },
    dashboard: {
        basic: {
            title: 'Willkommen im unite cms {{#unite._version}} version {{unite._version}}{{/unite._version}} Dashboard!'
        }
    },
    content: {
        list: {
            field: {
                fallback_warning: 'Feld Type "{fieldType}" für das Feld "{id}" ist nicht implementiert.',
            },
            view: {
                fallback_warning: 'View type "{viewType}" für die View "{id}" ist nicht implementiert.',
            },
            deleted: {
                active: 'Aktiv',
                deleted: 'Gelöscht'
            },
            actions: {
                create: 'Erstellen',
                update: 'Bearbeiten',
                translate: 'Übersetzungen verwalten',
                delete: 'Löschen',
                permanent_delete: 'Unwiederruflich entfernen',
                revert: 'Versionen verwalten',
                recover: 'Wiederherstellen',
                user_invite: 'User zu unite cms einladen'
            },
            selection: {
                select: 'Auswählen',
                confirm: '{count} ausgewählte Elemente übernehmen'
            },
            empty_placeholder: "Es wurde kein Inhalt für diese Ansicht gefunden.",
            search: {
                placeholder: "Suchen...",
                placeholder_filter: "Erweiterte Suchfilter angewendet..."
            },
            filter: {
                title: 'Erweiterte Suchfilter',
                clear: 'Löschen',
                cancel: 'Abbrechen',
                apply: 'Übernehmen',
                field: "Feld",
                operator: 'Vergleich',
                placeholder: "Wert einfügen / auswählen...",
                checkbox_true: 'Wahr',
                checkbox_false: 'Falsch',
                checkbox_null: 'Leer',
                filter: "Filter",
                AND: 'UND',
                OR: 'ODER'
            }
        },
        form: {
            field: {
                fallback_warning: 'Feld Type "{fieldType}" für das Feld "{id}" ist nicht implementiert.',
            },
        },
        create: {
            headline: 'Erstelle "{contentTitle}"',
            actions: {
                submit: 'Speichern'
            },
            success: '"{contentTitle}" wurde erstellt.',
            errors: '"{contentTitle}" konnte nicht erstellt werden. Bitte überprüfe die Validierungsfehler im Formular.',
        },
        update: {
            headline: '"{contentTitle}" bearbeiten',
            actions: {
                submit: 'Speichern'
            },
            success: '"{contentTitle}" wurde aktualisiert.',
            errors: '"{contentTitle}" konnte nicht aktualisiert werden. Bitte überprüfe die Validierungsfehler im Formular.',
        },
        delete: {
            headline: '"{contentTitle}" in den Papierkorb legen',
            message: 'Möchtest du "{contentTitle}" wirklich inden Papierkorb legen?',
            actions: {
                submit: 'In den Papierkorb legen'
            },
            success: '"{contentTitle}" wurde in den Papierkorb gelegt.',
        },
        recover: {
            headline: 'Wiederherstellung von "{contentTitle}"',
            message: 'Möchtest du "{contentTitle}" wiederherstellen?',
            actions: {
                submit: 'Wiederherstellen'
            },
            success: '"{contentTitle}" wurde wiederhergestellt.',
        },
        revert: {
            headline: 'Versionen von "{contentTitle}"',
            success: '"{contentTitle}" wurde zu Version {version} zurückgesetzt.',
            confirm: 'Möchtest du "{contentTitle}" wirklich zu Version {version} zurücksetzen?',
            label: {
                current: 'Aktuelle Version'
            },
            header: {
                version: '#',
                operation: 'Operation',
                meta: 'Meta'
            },
            actions: {
                revert: 'Zu dieser Version zurücksetzen'
            }
        },
        translate: {
            headline: 'Übersetzungen von "{contentTitle}"',
            select_translation: 'Wähle einen Inhalt aus um ihn als Übersetzung für den aktuellen Inhalt auszuwählen',
            header: {
                locale: 'Sprache'
            },
            no_locale_warning: 'Es wurde noch keine Sprache für diesen Inhalt festgelegt.'
        },
        permanent_delete: {
            headline: '"{contentTitle}" unwiederruflich entfernen',
            message: 'Möchtest du "{contentTitle}" wirklich unwiederruflich entfernen? Achtung! Diese Aktion kann nicht rückgängig gemacht werden.',
            actions: {
                submit: 'Unwiederruflich entfernen'
            },
            success: '"{contentTitle}" wurde entfernt.',
        },
        user_invite: {
            default_text: 'Das ist eine Einladung zu unite cms. Bitte klicken Sie den folgenden Link an, um die Einladung zu bestätigen.',
            headline: '"{contentTitle}" zu unite cms einladen',
            actions: {
                submit: 'Einladungs E-Mail senden'
            },
            success: '"{contentTitle}" wurde zu unite cms eingeladen.',
            error: '"{contentTitle}" konnte nicht zu unite cms eingeladen werdern. Das liegt wahrscheinlich daran, dass bereits eine Einladung für diesen User existiert. Bitte warte einige Zeit und versuche es erneut.',
        }
    },
    field: {
        reference: {
            missing_view_warning: 'Es wurde keine AdminView für den referenzierten Inhalt dieses Feldes gefunden. Bitte füge eine AdminView zu deinem Schema.',
            missing_required_value: 'Im Moment kann kein Inhalt ausgewählt werden, da zumindest ein Feld, das für dieses Feld notwendig ist, noch nicht ausgefüllt wurde.',
            modal: {
                headline: 'Wähle Inhalte aus, die du referenzieren möchtest'
            }
        },
        reference_of: {
            no_content_id: 'Eine Referenz zu diesem Feld ist erst nach der Erstellung möglich.',
            modal: {
                headline: 'Referenzierte {name} von "{contentTitle}"'
            }
        },
        geoLocation: {
            placeholder: {
                stairs_number: 'Stiege',
                door_number: 'Tür'
            },
            modal: {
                headline: '{display_name}'
            }
        },
        embedded: {
            confirm: {
                clear_union_selection: 'Möchtest du wirklich einen anderen Typen auswählen? Deine bisherigen Eingaben für dieses Feld gehen verloren.'
            }
        },
        sequence: {
            no_value_message: 'Der Wert dieses Feldes wird automatisch beim Erstellen generiert.'
        },
        checkbox: {
            description_true: '{label}: Wahr',
            description_false: '{label}: Falsch',
        },

        date: de
    },
    network_error: {
        401: 'Falscher Username oder falsches Passwort',
        500: 'Die API konnte nicht erreicht werden. Ist das GraphQL Schema valide?',
    },

    login: {
        headline: 'Anmelden',
        labels: {
            username: 'Username',
            password: 'Passwort'
        },
        actions: {
            submit: 'Anmelden',
            reset_password: 'Passwort vergessen?'
        }
    },

    reset_password: {
        headline: 'Passwort zurücksetzen',
        text: 'Bitte gib deinen Username ein und wir senden einen Token zum Zurücksetzen des Passwortes an deine E-Mail Adresse.',
        labels: {
            username: 'Username',
        },
        actions: {
            submit: 'Passwort zurücksetzen',
            login: 'Anmelden'
        },
        error: 'Passwort konnte nicht zurück gesetzt werden. Bitte warte einige Zeit und versuche es erneut.',
        success: 'Wir haben dir soeben einen Token zum Zurücksetzen des Passswortes an deine E-Mail Adresse geschickt.'
    },

    email_confirm: {
        invite: {
            headline: 'Einladung akzeptieren',
            text: 'Du wurdest eingeladen, einen unite cms "{type}" Account zu erstellen. Bitte wähle ein Passwort für deinen neuen Account.',
            labels: {
                username: 'Username (kann nicht bearbeitet werden)',
                password: 'Passwort',
                password_repeat: 'Passwort wiederholen',
            },
            actions: {
                submit: 'Account erstellen'
            },
            error: 'Account konnte nicht erstellt werden. Bitte wende dich an eine_n Administrator_in.',
            success: 'Dein Account wurde erfolgreich erstellt! Du wirst automatisch angemeldet...',
            token_expired: 'Der Einladungs-Token ist abgelaufen. Bitte wende dich an eine_n Administrator_in um eine neue Einladung zu bekommen.'
        },

        reset_password: {
            headline: 'Passwort zurücksetzen',
            text: 'Neues Passwort für deinen "{type}" User festlegen.',
            labels: {
                username: 'Username (kann nicht bearbeitet werden)',
                password: 'Neues Passwort',
                password_repeat: 'Neues Passwort wiederholen',
            },
            actions: {
                submit: 'Neues Passwort speichern'
            },
            error: 'Dein neues Passwort konnte nicht gespeichert werden. Bitte wende dich an eine_n Administrator_in.',
            success: 'Dein Passwort wurde aktualisiert! Du wirst automatisch angemeldet...',
            token_expired: 'Der Token zum Zurücksetzen des Passwortes ist angelaufen. Bitte fordere einen neuen Token zum Zurücksetzen des Passwortes an.'
        }
    }
};

// Set en part of dateTimeFormats.
dateTimeFormats.de = {
    full: {
        year: '2-digit', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', seconds: '2-digit', hour12: false
    },
    date: {
        year: '2-digit', month: '2-digit', day: '2-digit'
    }
};

// If we include this file, set the language to "de".
i18n.locale = 'de';
