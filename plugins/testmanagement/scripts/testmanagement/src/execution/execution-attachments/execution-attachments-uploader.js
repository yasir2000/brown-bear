/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { Upload } from "tus-js-client";

export function processUpload(file, file_upload_url, on_progress_callback, on_error_callback) {
    const uploader = new Upload(file, {
        uploadUrl: file_upload_url,
        metadata: {
            filename: file.name,
            filetype: file.type,
        },
        onProgress: (bytes_uploaded, bytes_total) => {
            on_progress_callback(Math.trunc((bytes_uploaded / bytes_total) * 100));
        },
        onError: on_error_callback,
    });

    uploader.start();
}

export function abortFileUpload(file_upload_url) {
    return Upload.terminate(file_upload_url);
}
