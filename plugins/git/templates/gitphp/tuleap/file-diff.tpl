{*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

{php}
    $this->assign(
        'potentially_dangerous_bidirectional_text_warning',
        \Tuleap\Git\Unicode\DangerousUnicodeText::getCodePotentiallyDangerousBidirectionalUnicodeTextWarning(
            implode($this->get_template_vars('diff'))
        )
    );
{/php}
{if $potentially_dangerous_bidirectional_text_warning}
    <div class="tlp-alert-warning">
        {$potentially_dangerous_bidirectional_text_warning}
    </div>
{/if}
<div class="git-repository-diff">
    {foreach from=$diff item=diffline}
        {if substr($diffline,0,1)=="+"}
            <div class="git-repository-diff-line git-repository-diff-line-added">{$diffline|escape:'html'}</div>
        {elseif substr($diffline,0,1)=="-"}
            <div class="git-repository-diff-line git-repository-diff-line-deleted">{$diffline|escape:'html'}</div>
        {elseif substr($diffline,0,1)=="@"}
            <div class="git-repository-diff-line git-repository-diff-line-at">{$diffline|escape:'html'}</div>
        {else}
            <div class="git-repository-diff-line">{$diffline|escape:'html'}</div>
        {/if}
    {/foreach}
</div>
