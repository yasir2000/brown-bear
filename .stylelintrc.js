const fs = require('fs')

const prettier_config = JSON.parse(fs.readFileSync("./.prettierrc", "utf-8"));

module.exports = {
    extends: ["stylelint-config-sass-guidelines", "stylelint-config-property-sort-order-smacss", "stylelint-config-standard-scss", "stylelint-config-html/vue", "stylelint-prettier/recommended"],
    reportNeedlessDisables: true,
    reportInvalidScopeDisables: true,
    rules: {
        "comment-word-disallowed-list": [
            [/^!/],
            {
                message: `Never use the "/*!" style of comments. Those comments are output in compressed CSS. (comment-word-disallowed-list)`
            }
        ],
        "color-hex-length": ["long"],
        "color-named": [
            "never",
            {
                message: "Colors should be written in hexadecimal format (color-named)"
            }
        ],
        "declaration-block-no-duplicate-properties": true,
        "function-calc-no-unspaced-operator": true,
        "max-nesting-depth": [4],
        "no-duplicate-selectors": true,
        "no-eol-whitespace": true,
        "number-leading-zero": ["always"],
        "selector-id-pattern": [
            "^[a-z0-9\\-]+$",
            {
                message:
                    "Selector should be written in lowercase with hyphens (selector-id-pattern)"
            }
        ],
        "selector-max-compound-selectors": [5],
        "selector-max-id": null,
        "selector-no-qualifying-type": [
            true,
            {
                ignore: ["attribute"]
            }
        ],
        "unit-allowed-list": [
            "em",
            "rem",
            "px",
            "vh",
            "vw",
            "vmin",
            "vmax",
            "deg",
            "grad",
            "rad",
            "turn",
            "ms",
            "s",
            "%",
            "fr"
        ],
        // Plugins
        "order/properties-alphabetical-order": null,
        "scss/at-else-empty-line-before": ["never"],
        "scss/dollar-variable-colon-space-before": null,
        "scss/operator-no-unspaced": true,
        "font-family-no-missing-generic-family-keyword": null,
        "no-descending-specificity": null, // Need a lot of work with existing files
        "no-invalid-position-at-import-rule": null, // Need work with existing files
        "scss/operator-no-newline-after": null, // Does not play well with Prettier
        "max-line-length": null, // Managed by Prettier
        "indentation": null, // Managed by Prettier
        "prettier/prettier": [true, prettier_config]
    }
};
