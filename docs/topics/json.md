# JSON

This document describes the canonical JSON format used to represent WikibaseLexeme entities in the API, in JSON dumps, as well as by Special:EntityData (when using JSON output).
This format can be expected to be reasonably stable, and is designed with flexibility and robustness in mind.

Lexemes are Wikibase entities.
While they do not have labels, descriptions, aliases, sitelinks, or a datatype, they do share all the other characteristics of Wikibase entities.
For those please see the [Wikibase JSON documentation](https://doc.wikimedia.org/Wikibase/master/php/docs_topics_json.html).

In particular, Lexemes have the same structure for Statements as other entities, see the respective [section about Statements](https://doc.wikimedia.org/Wikibase/master/php/docs_topics_json.html#json_statements) in the above documentation.

## Lemmas

A Lexeme can have one or multiple lemmas, at most one for each language(-variant).

```json
{
  "lemmas": {
    "en-gb": {
      "language": "en-gb",
      "value": "colour"
    },
    "en-ca": {
      "language": "en-ca",
      "value": "colour"
    },
    "en-us": {
      "language": "en-us",
      "value": "color"
    }
  }
}
```
For each language variant, there is a record using the following fields:

* language
  * The language code.
* value
  * The actual lemma as a literal string.

Lemmas have the same basic structure as labels, descriptions, and aliases in the Wikibase JSON.


## Language and Lexical Category

```json
{
  "lexicalCategory": "Q1084",
  "language": "Q1860"
}
```
The language and lexical category of a Lexeme are represented by their respective Item IDs.


## Forms and Senses

Forms and Senses are "subentities" of a Lexeme.
They can have their own set of statements, but each also have some extra keys that are specific to them.

### Forms

```json
{
  "forms": [
    {
      "id": "L1347-F1",
      "representations": {
        "en-gb": {
          "language": "en-gb",
          "value": "colour"
        },
        "en-ca": {
          "language": "en-ca",
          "value": "colour"
        },
        "en-us": {
          "language": "en-us",
          "value": "color"
        }
      },
      "grammaticalFeatures": [
        "Q110786"
      ],
      "claims": {}
    },
    {
      "id": "L1347-F2",
      "representations": {
        "en-gb": {
          "language": "en-gb",
          "value": "colours"
        },
        "en-ca": {
          "language": "en-ca",
          "value": "colours"
        },
        "en-us": {
          "language": "en-us",
          "value": "colors"
        }
      },
      "grammaticalFeatures": [
        "Q146786"
      ],
      "claims": {}
    }
  ]
}
```

Each Form has a unique `id`. That id consists of two parts, separated by a dash: the Lexeme id, and the number of the Form.

Also, a Form is associated with a set of one or more grammatical features, which are listed by their respective Item IDs.

Finally, a form has one or more representations, which are structured the same way as lemmas:
objects, indexed by language code, with the following fields:

* language
  * The language code.
* value
  * The actual representation as a literal string.

As mentioned above, Forms also can have statements in the usual structure under the `claims` key.

### Senses

```json
{
  "senses": [
    {
      "id": "L1347-S1",
      "glosses": {
        "en": {
          "language": "en",
          "value": "visual perception of light wavelengths"
        },
        "es": {
          "language": "es",
          "value": "percepción visual de las longitudes de onda de la luz visible"
        }
      },
      "claims": {
        "P5137": [
          {
            "mainsnak": {
              "snaktype": "value",
              "property": "P5137",
              "hash": "20be8699148157ed9ca7aae29324c34cc6ab4a08",
              "datavalue": {
                "value": {
                  "entity-type": "item",
                  "numeric-id": 1075,
                  "id": "Q1075"
                },
                "type": "wikibase-entityid"
              },
              "datatype": "wikibase-item"
            },
            "type": "statement",
            "id": "L1347-S1$F4D34950-4431-4AC0-8883-0C728C7860EA",
            "rank": "normal"
          }
        ]
      }
    }
  ]
}
```

Each Sense has a unique `id`. That id consists of two parts, separated by a dash: the Lexeme id, and the number of the Sense.

A Sense has one or more glosses associated with it, which are structured the same way as lemmas:
objects, indexed by language code, with the following fields:

* language
  * The language code.
* value
  * The actual gloss as a literal string.

As mentioned above, Senses also can have statements in the usual structure under the `claims` key.
