<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\OAuth2ServerCore\Grant\AuthorizationCode;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class OAuth2AuthorizationCodeDAO extends DataAccessObject
{
    public function create(
        int $app_id,
        int $user_id,
        string $hashed_verification_string,
        int $expiration_date_timestamp,
        ?string $pkce_code_challenge,
        ?string $oidc_nonce,
    ): int {
        return (int) $this->getDB()->insertReturnId(
            'oauth2_authorization_code',
            [
                'app_id'                => $app_id,
                'user_id'               => $user_id,
                'verifier'              => $hashed_verification_string,
                'expiration_date'       => $expiration_date_timestamp,
                'pkce_code_challenge'   => $pkce_code_challenge,
                'oidc_nonce'            => $oidc_nonce,
                'has_already_been_used' => false,
            ]
        );
    }

    /**
     * @psalm-return null|array{verifier:string,user_id:int,expiration_date:int,has_already_been_used:0|1,pkce_code_challenge:?string,oidc_nonce:?string}
     */
    public function searchAuthorizationCode(int $authorization_code_id): ?array
    {
        return $this->getDB()->row(
            'SELECT oauth2_authorization_code.verifier, user_id, expiration_date, has_already_been_used, pkce_code_challenge, oidc_nonce
                       FROM oauth2_authorization_code
                       JOIN oauth2_server_app ON oauth2_authorization_code.app_id = oauth2_server_app.id
                       LEFT JOIN `groups` ON oauth2_server_app.project_id = `groups`.group_id
                       WHERE oauth2_authorization_code.id = ? AND (`groups`.status = "A" OR oauth2_server_app.project_id IS NULL)',
            $authorization_code_id
        );
    }

    public function markAuthorizationCodeAsUsed(int $authorization_code_id): void
    {
        $this->getDB()->run(
            'UPDATE oauth2_authorization_code SET has_already_been_used=TRUE WHERE id=?',
            $authorization_code_id
        );
    }

    public function deleteAuthorizationCodeByAppID(int $app_id): void
    {
        $this->deleteAuthorizationCode(
            EasyStatement::open()->with('oauth2_authorization_code.app_id = ?', $app_id)
        );
    }

    public function deleteAuthorizationCodeByUserAndAppID(\PFUser $user, int $app_id): void
    {
        $this->deleteAuthorizationCode(
            EasyStatement::open()->with(
                'oauth2_authorization_code.user_id = ? AND oauth2_authorization_code.app_id = ?',
                $user->getId(),
                $app_id
            )
        );
    }

    public function deleteAuthorizationCodeByID(int $authorization_code_id): void
    {
        $this->deleteAuthorizationCode(
            EasyStatement::open()->with('oauth2_authorization_code.id = ?', $authorization_code_id)
        );
    }

    public function deleteAuthorizationCodeByExpirationDate(int $current_time): void
    {
        $this->deleteAuthorizationCode(
            EasyStatement::open()->with(
                '? > oauth2_authorization_code.expiration_date
                AND (oauth2_refresh_token.id IS NULL OR ? > oauth2_refresh_token.expiration_date)
                AND (oauth2_access_token.id IS NULL OR ? > oauth2_access_token.expiration_date)',
                $current_time,
                $current_time,
                $current_time
            )
        );
    }

    public function deleteAuthorizationCodeByUser(\PFUser $user): void
    {
        $this->deleteAuthorizationCode(
            EasyStatement::open()->with(
                'oauth2_authorization_code.user_id = ?',
                $user->getId()
            )
        );
    }

    public function deleteAuthorizationCodeInNonExistingOrDeletedProject(): void
    {
        $this->deleteAuthorizationCode(
            EasyStatement::open()->with('`groups`.group_id IS NULL OR `groups`.status = "D"')
        );
    }

    private function deleteAuthorizationCode(EasyStatement $filter_statement): void
    {
        $this->getDB()->safeQuery(
            "DELETE oauth2_authorization_code.*,
                              oauth2_authorization_code_scope.*,
                              oauth2_access_token.*,
                              oauth2_access_token_scope.*,
                              oauth2_refresh_token.*,
                              oauth2_refresh_token_scope.*
                       FROM oauth2_authorization_code
                       LEFT JOIN oauth2_authorization_code_scope ON oauth2_authorization_code.id = oauth2_authorization_code_scope.auth_code_id
                       LEFT JOIN oauth2_access_token ON oauth2_authorization_code.id = oauth2_access_token.authorization_code_id
                       LEFT JOIN oauth2_access_token_scope on oauth2_access_token.id = oauth2_access_token_scope.access_token_id
                       LEFT JOIN oauth2_refresh_token ON oauth2_authorization_code.id = oauth2_refresh_token.authorization_code_id
                       LEFT JOIN oauth2_refresh_token_scope ON oauth2_refresh_token.id = oauth2_refresh_token_scope.refresh_token_id
                       LEFT JOIN oauth2_server_app ON oauth2_authorization_code.app_id = oauth2_server_app.id
                       LEFT JOIN `groups` ON oauth2_server_app.project_id = `groups`.group_id
                       WHERE $filter_statement",
            $filter_statement->values()
        );
    }
}
