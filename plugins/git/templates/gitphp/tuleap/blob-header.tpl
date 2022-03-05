{*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
 * Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
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
 *}

<section class="tlp-pane-header git-repository-blob-header">
    {include file='tuleap/blob-header-title.tpl'}

    <div class="git-repository-blob-header-actions">
        <a {if isset($special_download_url) && $special_download_url}
               href="/{$special_download_url|urlencode}"
           {else}
               href="?a=blob_plain&amp;h={$blob->GetHash()|urlencode}&amp;f={$blob->GetPath()|urlencode}&amp;noheader=1"
           {/if}
           class="tlp-button-primary tlp-button-outline tlp-button-small git-repository-blob-header-plain"
           title="{t domain="gitphp"}Download file{/t}"
        >
            {t domain="gitphp"}Download{/t}
        </a>
        {if $blob->GetPath()}
            {if isset($datatag) || (isset($is_binaryfile) && $is_binaryfile) || (isset($is_file_in_special_format) && $is_file_in_special_format)}
                <a href="?a=history&amp;hb={$commit->GetHash()|urlencode}&amp;h={$commit->GetHash()|urlencode}&amp;f={$blob->GetPath()|urlencode}"
                   class="tlp-button-primary tlp-button-outline tlp-button-small git-repository-blob-header-history-datatag"
                >
                    {t domain="gitphp"}History{/t}
                </a>
            {else}
                <div class="tlp-button-bar git-repository-blob-header-actions-bar">
                    {if isset($can_be_rendered) && $can_be_rendered}
                        {if isset($rendered_file) && $rendered_file}
                            <div class="tlp-button-bar-item">
                                <a href="?a=blob&amp;hb={$commit->GetHash()|urlencode}&amp;h={$blob->GetHash()|urlencode}&amp;f={$blob->GetPath()|urlencode}&amp;show_source=1"
                                   class="tlp-button-primary tlp-button-outline tlp-button-small"
                                >
                                    {t domain="gitphp"}Show source{/t}
                                </a>
                            </div>
                        {else}
                            <div class="tlp-button-bar-item">
                                <a href="?a=blob&amp;hb={$commit->GetHash()|urlencode}&amp;h={$blob->GetHash()|urlencode}&amp;f={$blob->GetPath()|urlencode}"
                                   class="tlp-button-primary tlp-button-outline tlp-button-small"
                                >
                                    {t domain="gitphp"}Display rendered file{/t}
                                </a>
                            </div>
                        {/if}
                    {/if}
                    <div class="tlp-button-bar-item">
                        <a href="?a=blame&amp;f={$blob->GetPath()|urlencode}&amp;hb={$commit->GetHash()|urlencode}"
                           class="tlp-button-primary tlp-button-outline tlp-button-small"
                        >
                            {t domain="gitphp"}Blame{/t}
                        </a>
                    </div>
                    <div class="tlp-button-bar-item">
                        <a href="?a=history&amp;hb={$commit->GetHash()|urlencode}&amp;h={$commit->GetHash()|urlencode}&amp;f={$blob->GetPath()|urlencode}"
                            class="tlp-button-primary tlp-button-outline tlp-button-small"
                        >
                            {t domain="gitphp"}History{/t}
                        </a>
                    </div>
                </div>
            {/if}
        {/if}
    </div>
</section>
