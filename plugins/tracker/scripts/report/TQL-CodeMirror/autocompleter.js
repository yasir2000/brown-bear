/**
 * Copyright (c) Enalean 2017 - Present. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

import CodeMirror from "codemirror";

export { getHint };

function getHint(editor, options) {
    const cursor = editor.getCursor(),
        token = editor.getTokenAt(cursor);

    if (isAutocompletable(token)) {
        return getAutocompletableHint(editor, options, cursor, token);
    }
}

function isAutocompletable(token) {
    return token.type === null || token.type === "variable";
}

function getAutocompletableHint(editor, options, cursor, token) {
    const start = getStartOfToken(editor);
    const end = cursor.ch;
    const from = CodeMirror.Pos(cursor.line, start);
    const to = CodeMirror.Pos(cursor.line, end);
    const text = new RegExp(token.string.trim(), "i");

    return {
        list: options.words.filter((field_name) => text.test(field_name)),
        from,
        to,
    };
}

function getStartOfToken(editor) {
    const cursor = editor.getCursor();
    const line = editor.getLine(cursor.line);
    let start = cursor.ch;
    const a_word = /[@\w]+/;

    while (start && a_word.test(line.charAt(start - 1))) {
        --start;
    }

    return start;
}
